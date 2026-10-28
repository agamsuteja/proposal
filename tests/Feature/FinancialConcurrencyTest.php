<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\GroupFunding;
use App\Models\Grup;
use App\Models\PencairanDana;
use App\Models\Proposal;
use App\Models\Rekening;
use App\Models\User;
use App\Services\DepartmentBudgetService as Budgets;
use App\Services\GroupFundingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\LegacySchema;
use Tests\TestCase;

class FinancialConcurrencyTest extends TestCase
{
    private User $owner;

    private User $finance;

    private Proposal $proposal;

    private Rekening $account;

    protected function setUp(): void
    {
        parent::setUp();
        if (! getenv('BUDGET_TEST_MYSQL_DATABASE')) {
            $this->markTestSkipped('Concurrency is exercised on MariaDB, not SQLite.');
        }
        LegacySchema::build();
        $group = Grup::create(['nama_grup' => 'Concurrent']);
        $d = Departemen::create(['nama_departemen' => 'Concurrent', 'grup_id' => $group->id]);
        $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-hash', 'role' => 'karyawan', 'is_active' => 1, 'departemen_id' => $d->id, 'grup_id' => $group->id]);
        $this->finance = User::create(['name' => 'Finance', 'email' => 'finance@example.test', 'password' => 'test-hash', 'role' => 'keuangan', 'is_active' => 1, 'departemen_id' => $d->id, 'grup_id' => $group->id]);
        $this->account = Rekening::create(['nama_rekening' => 'Concurrent', 'saldo' => '5000000']);
        app(GroupFundingService::class)->save($group->id, 2026, $this->account->id, '5000000', 'Pendanaan paralel');
        app(Budgets::class)->setBudget($d->id, 2026, '5000000');
        $this->proposal = Proposal::create(['user_id' => $this->owner->id, 'departemen_id' => $d->id, 'judul' => 'Concurrent test', 'jenis' => 'acara', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran' => '1000000', 'status' => 'disetujui_senior_pastor']);

    }

    private function parallel(string $kind, array $first, array $second): array
    {
        $jobs = [];
        $start = (string) (microtime(true) + .4);
        foreach ([$first, $second] as $payload) {
            $process = proc_open([PHP_BINARY, base_path('tests/Support/financial-worker.php'), DB::connection()->getDatabaseName(), $kind, json_encode($payload), $start],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $jobs[] = [$process, $pipes];
        }
        $statuses = [];
        foreach ($jobs as [$process,$pipes]) {
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($process), $err);
            $statuses[] = trim($out);
        }
        sort($statuses);

        return $statuses;
    }

    private function payout(?string $id = null): array
    {
        return ['_actor' => $this->finance->id, 'operation_id' => $id ?? (string) Str::uuid(), 'proposal_id' => $this->proposal->id, 'rekening_id' => $this->account->id, 'jumlah_dana' => '750000', 'metode' => 'cash', 'catatan' => 'Concurrent payment'];
    }

    public function test_parallel_payouts_cannot_exceed_proposal_ceiling(): void
    {
        $this->assertSame(['302', '422'], $this->parallel('payout', $this->payout(), $this->payout()));
        $this->assertDatabaseCount('pencairan_dana', 1);
        $this->assertSame(425000000, Budgets::cents($this->account->fresh()->saldo));
    }

    public function test_repeated_parallel_operation_is_applied_only_once(): void
    {
        $data = $this->payout();
        $this->assertSame(['302', '302'], $this->parallel('payout', $data, $data));
        $this->assertDatabaseCount('pencairan_dana', 1);
        $this->assertDatabaseCount('financial_operations', 1);
    }

    public function test_parallel_refund_requests_reserve_remaining_cash(): void
    {
        $this->proposal->update(['status' => 'dicairkan']);
        PencairanDana::create(['proposal_id' => $this->proposal->id, 'rekening_id' => $this->account->id, 'jumlah_dana' => '1000000']);
        $data = ['_actor' => $this->owner->id, 'operation_id' => (string) Str::uuid(), 'proposal_id' => $this->proposal->id, 'jumlah' => '750000', 'metode' => 'cash'];
        $other = $data;
        $other['operation_id'] = (string) Str::uuid();
        $this->assertSame(['302', '422'], $this->parallel('refund', $data, $other));
        $this->assertDatabaseCount('pengembalian_dana', 1);
    }

    public function test_parallel_group_funding_cannot_reserve_the_same_bank_cash_twice(): void
    {
        $account = Rekening::create(['nama_rekening' => 'Shared Bank', 'saldo' => '20000000']);
        $other = Grup::create(['nama_grup' => 'Other group']);
        $data = ['_actor' => $this->finance->id, 'grup_id' => $this->proposal->departemen->grup_id, 'tahun' => 2027, 'rekening_id' => $account->id, 'plafon' => '12000000', 'alasan' => 'Pendanaan bersamaan'];
        $second = $data;
        $second['grup_id'] = $other->id;
        $this->assertSame(['302', '422'], $this->parallel('funding', $data, $second));
        $this->assertSame(1200000000, app(GroupFundingService::class)->committed($account->id));
        $this->assertSame(1, GroupFunding::where('rekening_id', $account->id)->count());
    }

    public function test_parallel_department_allocations_cannot_exceed_group_limit(): void
    {
        $department = $this->proposal->departemen;
        app(Budgets::class)->setBudget($department->id, 2026, '1000000');
        $other = Departemen::create(['nama_departemen' => 'Another dept', 'grup_id' => $department->grup_id]);
        $data = ['_actor' => $this->finance->id, 'departemen_id' => $department->id, 'tahun' => 2026, 'jumlah' => '4000000', 'alasan' => 'Alokasi secara bersamaan'];
        $second = $data;
        $second['departemen_id'] = $other->id;
        $this->assertSame(['302', '422'], $this->parallel('allocation', $data, $second));
        $total = app(GroupFundingService::class)->allocated($department->grup_id, 2026);
        $this->assertLessThanOrEqual(500000000, $total);
    }
}
