<?php

namespace App\Services;

use App\Models\BudgetAdjustment;
use App\Models\BudgetApproval;
use App\Models\BudgetPeriod;
use App\Models\BudgetRequest;
use App\Models\BudgetRequestVersion;
use App\Models\BudgetReversal;
use App\Models\BudgetTransfer;
use App\Models\Departemen;
use App\Models\DepartmentBudget;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentBudgetService
{
    public static function cents($value): int
    {
        $value = (string) $value;
        if (! preg_match('/^(-?)([0-9]+)(?:\.([0-9]{1,2}))?$/D', $value, $parts)) {
            throw new \InvalidArgumentException('Non-canonical monetary amount');
        }
        $amount = (int) $parts[2] * 100 + (int) str_pad($parts[3] ?? '', 2, '0');

        return ($parts[1] ?? '') === '-' ? -$amount : $amount;
    }

    public static function amount(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    public function ensureOpen(int $year): void
    {
        BudgetPeriod::firstOrCreate(['tahun' => $year], ['status' => 'open']);
        $period = BudgetPeriod::whereKey($year)->lockForUpdate()->firstOrFail();
        if ($period->status !== 'open') {
            $this->fail('Periode '.$year.' ditutup. Hubungi keuangan untuk membukanya kembali.');
        }
    }

    public function reserved(int $department, int $year, ?int $except = null): int
    {
        $payments = DB::table('pencairan_dana')->selectRaw('proposal_id, SUM(jumlah_dana) AS paid')->groupBy('proposal_id');
        $value = DB::table('proposal')->leftJoinSub($payments, 'payments', fn ($j) => $j->on('proposal.id', '=', 'payments.proposal_id'))
            ->where('departemen_id', $department)->whereYear('tanggal_pengajuan', $year)
            ->whereIn('status', ['disetujui_senior_pastor', 'dicairkan_sebagian'])
            ->when($except, fn ($q) => $q->where('proposal.id', '!=', $except))
            ->selectRaw('COALESCE(SUM(CASE WHEN total_anggaran > COALESCE(payments.paid,0) THEN total_anggaran - COALESCE(payments.paid,0) ELSE 0 END),0) AS total')->value('total');

        return self::cents($value);
    }

    public function spendable(int $department, int $year, ?int $except = null): int
    {
        return self::cents(DepartmentBudget::where('departemen_id', $department)->where('tahun', $year)->value('jumlah') ?? 0) - $this->used($department, $year) - $this->reserved($department, $year, $except);
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
        return $this->spendable($department, $year);
    }

    public function setBudget(int $department, int $year, string $amount, string $reason = 'Alokasi awal'): void
    {
        DB::transaction(function () use ($department, $year, $amount, $reason) {
            $this->ensureOpen($year);
            Departemen::whereKey($department)->lockForUpdate()->firstOrFail();
            if (self::cents($amount) < $this->used($department, $year) + $this->reserved($department, $year)) {
                $this->fail('Alokasi tidak boleh kurang dari dana terpakai dan dicadangkan.');
            }
            $budget = DepartmentBudget::firstOrNew(['departemen_id' => $department, 'tahun' => $year]);
            $before = $budget->jumlah ?? '0';
            if (auth()->check()) {
                BudgetAdjustment::create(['departemen_id' => $department, 'tahun' => $year, 'before_amount' => $before, 'after_amount' => $amount, 'alasan' => $reason, 'user_id' => auth()->id()]);
            }
            $budget->jumlah = $amount;
            $budget->save();
        }, 3);
    }

    public function submit(User $user, Departemen $department, int $year, string $amount, string $reason): BudgetRequest
    {
        return DB::transaction(function () use ($user, $department, $year, $amount, $reason) {
            $this->ensureOpen($year);
            $approvers = app(ProposalApprovers::class)->forDepartment($department);
            $request = BudgetRequest::create(['user_id' => $user->id, 'departemen_id' => $department->id,
                'tahun' => $year, 'jumlah' => $amount, 'alasan' => $reason, 'status' => 'pending']);
            $order = 1;
            foreach ($approvers as $stage => $approver) {
                BudgetApproval::create(['budget_request_id' => $request->id, 'approver_id' => $approver->id,
                    'tahap' => $stage, 'urutan' => $order++, 'status' => 'pending']);
            }
            $this->notifyNext($request);

            return $request;
        });
    }

    public function decide(int $id, User $user, string $status, ?string $note): void
    {
        $year = BudgetRequest::findOrFail($id)->tahun;
        DB::transaction(function () use ($id, $user, $status, $note, $year) {
            $this->ensureOpen($year);
            $request = BudgetRequest::lockForUpdate()->findOrFail($id);
            if ($request->status !== 'pending') {
                $this->fail('Pengajuan ini sudah diproses.');
            }
            $approval = $request->approvals()->where('status', 'pending')->first();
            abort_unless($approval && $approval->approver_id === $user->id && $approval->tahap === $user->role && $user->is_active, 403);
            if ($user->role === 'departemen_head') {
                abort_unless($user->departemen_id === $request->departemen_id, 403);
            }
            if ($user->role === 'group_head') {
                abort_unless($user->grup_id === $request->departemen->grup_id, 403);
            }
            $approval->update(['status' => $status, 'catatan' => $note, 'decided_at' => now()]);
            if ($status === 'ditolak') {
                $request->update(['status' => 'ditolak']);
            } elseif (! $request->approvals()->where('status', 'pending')->exists()) {
                $request->update(['status' => 'disetujui']);
            }
            if ($status === 'ditolak') {
                app(NotificationService::class)->queue($request->user->nohp, 'Pengajuan tambahan budget #'.$request->id.' ditolak. Silakan periksa catatan dan revisi.', 'Tambahan budget ditolak', $request->user_id);
            } else {
                $this->notifyNext($request);
            }
        });
    }

    public function fund(int $id, User $user, array $sources): void
    {
        abort_unless($user->role === 'keuangan' && $user->is_active, 403);
        $year = BudgetRequest::findOrFail($id)->tahun;
        DB::transaction(function () use ($id, $user, $sources, $year) {
            $this->ensureOpen($year);
            $request = BudgetRequest::lockForUpdate()->findOrFail($id);
            if ($request->status !== 'disetujui' || $request->funded_at) {
                $this->fail('Pengajuan belum disetujui atau sudah ditransfer.');
            }
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
                if (! $department || $sourceId === $target->id || $department->grup_id !== $target->grup_id || isset($seen[$sourceId]) || $amount <= 0) {
                    $this->fail('Pilih sumber berbeda dalam grup yang sama, masing-masing satu kali.');
                }
                $seen[$sourceId] = true;
                if ($amount > $this->available($sourceId, $request->tahun)) {
                    $this->fail('Saldo tersedia departemen '.$department->nama_departemen.' tidak mencukupi.');
                }
                $total += $amount;
            }
            if ($total !== self::cents($request->jumlah)) {
                $this->fail('Total transfer harus sama dengan nominal pengajuan yang disetujui.');
            }
            $targetBudget = DepartmentBudget::firstOrCreate(['departemen_id' => $target->id, 'tahun' => $request->tahun], ['jumlah' => 0]);
            foreach ($sources as $source) {
                $budget = DepartmentBudget::where('departemen_id', $source['departemen_id'])->where('tahun', $request->tahun)->firstOrFail();
                $budget->update(['jumlah' => self::amount(self::cents($budget->jumlah) - self::cents($source['jumlah']))]);
                BudgetTransfer::create(['budget_request_id' => $request->id, 'sumber_id' => $source['departemen_id'],
                    'tujuan_id' => $target->id, 'user_id' => $user->id, 'tahun' => $request->tahun, 'jumlah' => $source['jumlah']]);
            }
            $newTotal = self::cents($targetBudget->jumlah) + $total;
            if ($newTotal > 9999999999999999) {
                $this->fail('Total budget melebihi kapasitas nominal.');
            }
            $targetBudget->update(['jumlah' => self::amount($newTotal)]);
            $request->update(['status' => 'ditransfer', 'funded_at' => now()]);
            app(NotificationService::class)->queue($request->user->nohp, 'Tambahan budget #'.$request->id.' telah ditransfer ke departemen Anda.', 'Transfer budget selesai', $request->user_id, 'budget-funded-'.$request->id);
        }, 3);
    }

    private function notifyNext(BudgetRequest $request): void
    {
        $next = $request->approvals()->where('status', 'pending')->first();
        $recipients = $next ? collect([$next->approver]) : User::where('role', 'keuangan')->where('is_active', 1)->get();
        foreach ($recipients as $recipient) {
            app(NotificationService::class)->queue($recipient->nohp, 'Pengajuan tambahan budget #'.$request->id.' sebesar Rp '.number_format($request->jumlah, 2, ',', '.').' menunggu '.($next ? 'persetujuan Anda.' : 'transfer keuangan.'), 'Tambahan budget', $recipient->id, 'budget-next-'.$request->id.'-'.($next?->id ?? 'finance').'-'.$recipient->id);
        }
    }

    public function revise(int $id, User $user, string $amount, string $reason): void
    {
        $year = BudgetRequest::findOrFail($id)->tahun;
        DB::transaction(function () use ($id, $user, $amount, $reason, $year) {
            $this->ensureOpen($year);
            $r = BudgetRequest::lockForUpdate()->findOrFail($id);
            abort_unless($r->user_id === $user->id, 403);
            abort_unless($r->status === 'ditolak', 409, 'Hanya pengajuan ditolak yang dapat direvisi.');
            BudgetRequestVersion::create(['budget_request_id' => $id, 'user_id' => $user->id, 'snapshot' => ['request' => $r->toArray(), 'approvals' => $r->approvals->toArray()], 'alasan' => $reason]);
            $approvers = app(ProposalApprovers::class)->forDepartment($r->departemen);
            foreach ($r->approvals as $approval) {
                $approval->delete();
            }
            $r->update(['jumlah' => $amount, 'alasan' => $reason, 'status' => 'pending']);
            $order = 1;
            foreach ($approvers as $stage => $approver) {
                BudgetApproval::create(['budget_request_id' => $id, 'approver_id' => $approver->id, 'tahap' => $stage, 'urutan' => $order++, 'status' => 'pending']);
            }
            $this->notifyNext($r);
        }, 3);
    }

    public function cancel(int $id, User $user, string $reason): void
    {
        $year = BudgetRequest::findOrFail($id)->tahun;
        DB::transaction(function () use ($id, $user, $reason, $year) {
            $this->ensureOpen($year);
            $r = BudgetRequest::lockForUpdate()->findOrFail($id);
            abort_unless($r->user_id === $user->id, 403);
            abort_unless(in_array($r->status, ['pending', 'disetujui', 'ditolak']), 409);
            BudgetRequestVersion::create(['budget_request_id' => $id, 'user_id' => $user->id, 'snapshot' => $r->toArray(), 'alasan' => $reason]);
            $r->update(['status' => 'dibatalkan']);
        }, 3);
    }

    public function reassign(int $id, User $user, int $approverId, string $reason): void
    {
        abort_unless($user->role === 'admin', 403);
        $year = BudgetRequest::findOrFail($id)->tahun;
        DB::transaction(function () use ($id, $user, $approverId, $reason, $year) {
            $this->ensureOpen($year);
            $r = BudgetRequest::lockForUpdate()->findOrFail($id);
            abort_unless($r->status === 'pending', 409);
            $flow = $r->approvals()->where('status', 'pending')->firstOrFail();
            $replacement = User::findOrFail($approverId);
            abort_unless($replacement->is_active && $replacement->role === $flow->tahap, 422, 'Role atau status approver pengganti tidak sesuai.');
            if ($flow->tahap === 'departemen_head') {
                abort_unless($replacement->departemen_id === $r->departemen_id, 422);
            }
            if ($flow->tahap === 'group_head') {
                abort_unless($replacement->grup_id === $r->departemen->grup_id, 422);
            }
            BudgetRequestVersion::create(['budget_request_id' => $id, 'user_id' => $user->id, 'snapshot' => $flow->toArray(), 'alasan' => $reason]);
            $flow->update(['approver_id' => $replacement->id]);
            $this->notifyNext($r);
        }, 3);
    }

    public function reverse(int $id, User $user, string $reason): void
    {
        abort_unless($user->role === 'keuangan', 403);
        $year = BudgetRequest::findOrFail($id)->tahun;
        DB::transaction(function () use ($id, $user, $reason, $year) {
            $this->ensureOpen($year);
            $r = BudgetRequest::lockForUpdate()->findOrFail($id);
            abort_unless($r->status === 'ditransfer', 409);
            $transfers = $r->transfers;
            Departemen::whereIn('id', array_merge([$r->departemen_id], $transfers->pluck('sumber_id')->all()))->orderBy('id')->lockForUpdate()->get();
            if ($this->available($r->departemen_id, $year) < self::cents($r->jumlah)) {
                $this->fail('Budget penerima sudah digunakan atau dicadangkan. Transfer tidak dapat dibalik.');
            }
            $target = DepartmentBudget::where('departemen_id', $r->departemen_id)->where('tahun', $year)->firstOrFail();
            $target->update(['jumlah' => self::amount(self::cents($target->jumlah) - self::cents($r->jumlah))]);
            foreach ($transfers as $transfer) {
                $source = DepartmentBudget::where('departemen_id', $transfer->sumber_id)->where('tahun', $year)->firstOrFail();
                $source->update(['jumlah' => self::amount(self::cents($source->jumlah) + self::cents($transfer->jumlah))]);
            }
            BudgetReversal::create(['budget_request_id' => $id, 'user_id' => $user->id, 'alasan' => $reason]);
            $r->update(['status' => 'dibalik']);
        }, 3);
    }

    public function period(int $year, string $status, User $user, string $reason): void
    {
        abort_unless($user->role === 'keuangan', 403);
        DB::transaction(function () use ($year, $status, $user, $reason) {
            BudgetPeriod::firstOrCreate(['tahun' => $year], ['status' => 'open']);
            $p = BudgetPeriod::whereKey($year)->lockForUpdate()->firstOrFail();
            if ($status === 'closed' && (BudgetRequest::where('tahun', $year)->whereIn('status', ['pending', 'disetujui'])->exists() || Proposal::whereYear('tanggal_pengajuan', $year)->whereIn('status', ['diajukan', 'disetujui_mh', 'disetujui_gh', 'disetujui_senior_pastor', 'dicairkan_sebagian', 'revisi'])->exists())) {
                $this->fail('Masih ada proposal atau pengajuan tambahan yang belum selesai.');
            }
            $p->update(['status' => $status, 'user_id' => $user->id, 'alasan' => $reason]);
        }, 3);
    }

    public function copyYear(int $source, int $target, User $user, string $reason): void
    {
        abort_unless($user->role === 'keuangan' && $source !== $target, 403);
        DB::transaction(function () use ($source, $target, $reason) {
            $this->ensureOpen($target);
            if (DepartmentBudget::where('tahun',$target)->exists()) {
                $this->fail('Tahun tujuan sudah memiliki alokasi. Penyalinan tidak menimpa data.');
            }
            $budgets = DepartmentBudget::where('tahun',$source)->get();
            if ($budgets->isEmpty()) {
                $this->fail('Tahun sumber belum memiliki budget.');
            }
            foreach ($budgets as $budget) {
                $this->setBudget($budget->departemen_id,$target,(string) $budget->jumlah,$reason);
            }
        }, 3);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['budget' => $message]);
    }
}
