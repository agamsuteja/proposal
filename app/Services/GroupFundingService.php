<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BudgetPeriod;
use App\Models\Departemen;
use App\Models\DepartmentBudget;
use App\Models\GroupFunding;
use App\Models\Rekening;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GroupFundingService
{
    // A single stable lock protects shared account backing across groups and years.
    // Always acquire before year, department and account locks. Never lock during reads.
    public function lock(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Funding writes require a transaction.');
        }
        if (! DB::table('budget_funding_mutex')->where('id', 1)->lockForUpdate()->first()) {
            $this->fail('Konfigurasi pendanaan belum lengkap. Impor SQL pendanaan grup.');
        }
    }

    public function allocated(int $group, int $year): int
    {
        return DepartmentBudgetService::cents(DepartmentBudget::join('departemen', 'departemen.id', '=', 'budget_departemen.departemen_id')
            ->where('grup_id', $group)->where('tahun', $year)->sum('jumlah'));
    }

    public function used(int $group, int $year): int
    {
        $proposals = DB::table('proposal')->join('departemen', 'departemen.id', '=', 'proposal.departemen_id')
            ->where('departemen.grup_id', $group)->whereYear('tanggal_pengajuan', $year)->select('proposal.id');
        $paid = DepartmentBudgetService::cents(DB::table('pencairan_dana')->whereIn('proposal_id', clone $proposals)->sum('jumlah_dana'));
        $returned = DepartmentBudgetService::cents(DB::table('pengembalian_dana')->whereIn('proposal_id', $proposals)->where('status', 'diterima')->sum('jumlah'));
        if ($returned > $paid) {
            $this->fail('Pengembalian grup melebihi pencairan. Rekonsiliasi data lama terlebih dahulu.');
        }

        return $paid - $returned;
    }

    public function committed(int $account): int
    {
        $total = 0;
        $rows = GroupFunding::where('rekening_id', $account)->whereNotIn('tahun', BudgetPeriod::where('status', 'closed')->select('tahun'))->get();
        foreach ($rows as $row) {
            $total += max(0, DepartmentBudgetService::cents($row->plafon) - $this->used($row->grup_id, $row->tahun));
        }

        return $total;
    }

    public function assertBacked(int $account): void
    {
        $balance = DepartmentBudgetService::cents(Rekening::findOrFail($account)->saldo ?? 0);
        if ($this->committed($account) > $balance) {
            $this->fail('Saldo rekening tidak cukup untuk seluruh plafon grup dan tahun yang masih terbuka. Dana yang sama tidak boleh dialokasikan dua kali.');
        }
    }

    public function requireDepartment(int $department, int $year): GroupFunding
    {
        $group = Departemen::findOrFail($department)->grup_id;
        $funding = GroupFunding::where('grup_id', $group)->where('tahun', $year)->first();
        if (! $funding) {
            $this->fail('Rekening sumber dan plafon grup tahun '.$year.' belum ditetapkan. Hubungi keuangan.');
        }
        $this->assertBacked($funding->rekening_id);

        return $funding;
    }

    public function save(int $group, int $year, int $account, string $amount, string $reason): void
    {
        DB::transaction(function () use ($group, $year, $account, $amount, $reason) {
            app(DepartmentBudgetService::class)->ensureOpen($year);
            $row = GroupFunding::where('grup_id', $group)->where('tahun', $year)->first();
            Rekening::whereIn('id', array_unique([$account, $row?->rekening_id ?? $account]))->orderBy('id')->lockForUpdate()->get();
            $proposals = DB::table('proposal')->join('departemen', 'departemen.id', '=', 'proposal.departemen_id')
                ->where('departemen.grup_id', $group)->whereYear('tanggal_pengajuan', $year)->select('proposal.id');
            if (DB::table('pencairan_dana')->whereIn('proposal_id', clone $proposals)->where('rekening_id', '!=', $account)->exists()
                || DB::table('pengembalian_dana')->whereIn('proposal_id', $proposals)->where('status', 'diterima')->where(fn ($q) => $q->whereNull('rekening_id')->orWhere('rekening_id', '!=', $account))->exists()) {
                $this->fail('Riwayat pencairan/pengembalian grup memakai rekening lain atau belum jelas. Rekonsiliasi riwayat sebelum menetapkan rekening sumber.');
            }
            $minimum = $this->allocated($group, $year);
            $usedAndReserved = 0;
            foreach (Departemen::where('grup_id', $group)->pluck('id') as $id) {
                $usedAndReserved += app(DepartmentBudgetService::class)->used($id, $year) + app(DepartmentBudgetService::class)->reserved($id, $year);
            }
            if (DepartmentBudgetService::cents($amount) < max($minimum, $usedAndReserved)) {
                $this->fail('Plafon tidak boleh kurang dari total alokasi departemen atau dana terpakai dan dicadangkan.');
            }
            GroupFunding::updateOrCreate(['grup_id' => $group, 'tahun' => $year], ['rekening_id' => $account, 'plafon' => $amount, 'alasan' => $reason, 'user_id' => auth()->id()]);
            $this->assertBacked($account);
        }, 3);
    }

    public function distribute(int $group, int $year, string $reason): void
    {
        DB::transaction(function () use ($group, $year, $reason) {
            app(DepartmentBudgetService::class)->ensureOpen($year);
            $departments = Departemen::where('grup_id', $group)->orderBy('id')->lockForUpdate()->get();
            if ($departments->isEmpty()) {
                $this->fail('Grup belum memiliki departemen.');
            }
            $funding = $this->requireDepartment($departments->first()->id, $year);
            $total = DepartmentBudgetService::cents($funding->plafon);
            $base = intdiv($total, $departments->count());
            $remainder = $total % $departments->count();
            // Check every floor before changing any allocation; all changes commit together.
            foreach ($departments as $i => $department) {
                $share = $base + ($i < $remainder ? 1 : 0);
                if ($share < app(DepartmentBudgetService::class)->used($department->id, $year) + app(DepartmentBudgetService::class)->reserved($department->id, $year)) {
                    $this->fail('Pembagian rata membuat alokasi '.$department->nama_departemen.' kurang dari dana terpakai/cadangan. Gunakan pengaturan manual.');
                }
            }
            // Group limit is validated for the final set, not temporarily while replacing rows.
            foreach ($departments as $i => $department) {
                app(DepartmentBudgetService::class)->writeAllocation($department->id, $year, DepartmentBudgetService::amount($base + ($i < $remainder ? 1 : 0)), $reason);
            }
            ActivityLog::create(['user_id' => auth()->id(), 'action' => 'budget.distributed_equally', 'subject_type' => 'group_fundings', 'subject_id' => (string) $funding->id,
                'after' => ['jumlah_departemen' => $departments->count(), 'plafon' => $funding->plafon, 'alasan' => $reason]]);
        }, 3);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['pendanaan' => $message]);
    }
}
