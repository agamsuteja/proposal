<?php

namespace App\Services;

use App\Models\{BudgetApproval, BudgetRequest, BudgetTransfer, DepartmentBudget, Departemen, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentBudgetService
{
    // Integer cents avoid floating point drift when adding/transferring money.
    public static function cents($value): int
    {
        $parts = explode('.', (string) $value, 2);
        return (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    public static function amount(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    public function used(int $department, int $year): int
    {
        // Charge the proposal's submission year, including later refunds/disbursements.
        $proposals = DB::table('proposal')->where('departemen_id', $department)
            ->whereYear('tanggal_pengajuan', $year)->select('id');
        $paid = DB::table('pencairan_dana')->whereIn('proposal_id', clone $proposals)->sum('jumlah_dana');
        $returned = DB::table('pengembalian_dana')->whereIn('proposal_id', clone $proposals)
            ->where('status', 'diterima')->sum('jumlah');
        return max(0, self::cents($paid) - self::cents($returned));
    }

    public function available(int $department, int $year): int
    {
        return self::cents(DepartmentBudget::where('departemen_id', $department)->where('tahun', $year)->value('jumlah') ?? 0)
            - $this->used($department, $year);
    }

    public function setBudget(int $department, int $year, string $amount): void
    {
        DB::transaction(function () use ($department, $year, $amount) {
            Departemen::whereKey($department)->lockForUpdate()->firstOrFail();
            if (self::cents($amount) < $this->used($department, $year)) {
                $this->fail('Budget tidak boleh lebih kecil dari dana yang sudah terpakai.');
            }
            $budget = DepartmentBudget::firstOrNew(['departemen_id' => $department, 'tahun' => $year]);
            // Once transfers exist, direct edits would erase the approved transfer history.
            if (BudgetTransfer::where('tahun', $year)->where(fn ($q) => $q->where('sumber_id', $department)->orWhere('tujuan_id', $department))->exists()) {
                $this->fail('Budget yang sudah ditransfer tidak dapat ditimpa. Gunakan pengajuan tambahan budget.');
            }
            $budget->jumlah = $amount;
            $budget->save();
        });
    }

    public function submit(User $user, Departemen $department, int $year, string $amount, string $reason): BudgetRequest
    {
        return DB::transaction(function () use ($user, $department, $year, $amount, $reason) {
            $approvers = [
                'departemen_head' => User::where('role', 'departemen_head')->where('departemen_id', $department->id)->where('is_active', 1)->orderBy('id')->first(),
                'group_head' => User::where('role', 'group_head')->where('grup_id', $department->grup_id)->where('is_active', 1)->orderBy('id')->first(),
                'gembala' => User::where('role', 'gembala')->where('is_active', 1)->orderBy('id')->first(),
            ];
            foreach ($approvers as $stage => $approver) {
                if (!$approver) $this->fail('Approver aktif untuk tahap '.$stage.' belum tersedia.');
            }
            $request = BudgetRequest::create(['user_id' => $user->id, 'departemen_id' => $department->id,
                'tahun' => $year, 'jumlah' => $amount, 'alasan' => $reason, 'status' => 'pending']);
            $order = 1;
            foreach ($approvers as $stage => $approver) {
                BudgetApproval::create(['budget_request_id' => $request->id, 'approver_id' => $approver->id,
                    'tahap' => $stage, 'urutan' => $order++, 'status' => 'pending']);
            }
            return $request;
        });
    }

    public function decide(int $id, User $user, string $status, ?string $note): void
    {
        DB::transaction(function () use ($id, $user, $status, $note) {
            $request = BudgetRequest::lockForUpdate()->findOrFail($id);
            if ($request->status !== 'pending') $this->fail('Pengajuan ini sudah diproses.');
            $approval = $request->approvals()->where('status', 'pending')->first();
            abort_unless($approval && $approval->approver_id === $user->id && $approval->tahap === $user->role && $user->is_active, 403);
            if ($user->role === 'departemen_head') abort_unless($user->departemen_id === $request->departemen_id, 403);
            if ($user->role === 'group_head') abort_unless($user->grup_id === $request->departemen->grup_id, 403);
            $approval->update(['status' => $status, 'catatan' => $note, 'decided_at' => now()]);
            if ($status === 'ditolak') $request->update(['status' => 'ditolak']);
            elseif (!$request->approvals()->where('status', 'pending')->exists()) $request->update(['status' => 'disetujui']);
        });
    }

    public function fund(int $id, User $user, array $sources): void
    {
        abort_unless($user->role === 'keuangan' && $user->is_active, 403);
        DB::transaction(function () use ($id, $user, $sources) {
            $request = BudgetRequest::lockForUpdate()->findOrFail($id);
            if ($request->status !== 'disetujui' || $request->funded_at) $this->fail('Pengajuan belum disetujui atau sudah ditransfer.');
            // Serialize all budget/disbursement writes using department locks in ID order.
            $ids = array_merge([$request->departemen_id], array_column($sources, 'departemen_id'));
            $departments = Departemen::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $target = $departments[$request->departemen_id];
            $total = 0;
            $seen = [];
            foreach ($sources as $source) {
                $sourceId = (int) $source['departemen_id'];
                $department = $departments->get($sourceId);
                $amount = self::cents($source['jumlah']);
                if (!$department || $sourceId === $target->id || $department->grup_id !== $target->grup_id || isset($seen[$sourceId]) || $amount <= 0) {
                    $this->fail('Pilih sumber berbeda dalam grup yang sama, masing-masing satu kali.');
                }
                $seen[$sourceId] = true;
                if ($amount > $this->available($sourceId, $request->tahun)) $this->fail('Saldo tersedia departemen '.$department->nama_departemen.' tidak mencukupi.');
                $total += $amount;
            }
            if ($total !== self::cents($request->jumlah)) $this->fail('Total transfer harus sama dengan nominal pengajuan yang disetujui.');
            $targetBudget = DepartmentBudget::firstOrCreate(['departemen_id' => $target->id, 'tahun' => $request->tahun], ['jumlah' => 0]);
            foreach ($sources as $source) {
                $budget = DepartmentBudget::where('departemen_id', $source['departemen_id'])->where('tahun', $request->tahun)->firstOrFail();
                $budget->update(['jumlah' => self::amount(self::cents($budget->jumlah) - self::cents($source['jumlah']))]);
                BudgetTransfer::create(['budget_request_id' => $request->id, 'sumber_id' => $source['departemen_id'],
                    'tujuan_id' => $target->id, 'user_id' => $user->id, 'tahun' => $request->tahun, 'jumlah' => $source['jumlah']]);
            }
            $newTotal = self::cents($targetBudget->jumlah) + $total;
            if ($newTotal > 9999999999999999) $this->fail('Total budget melebihi kapasitas nominal.');
            $targetBudget->update(['jumlah' => self::amount($newTotal)]);
            $request->update(['status' => 'ditransfer', 'funded_at' => now()]);
        }, 3);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['budget' => $message]);
    }
}
