<?php

namespace Tests\Feature;

use App\Models\BudgetRequest;
use App\Models\Departemen;
use App\Models\DepartmentBudget;
use App\Models\GroupFunding;
use App\Models\Grup;
use App\Models\PengembalianDana;
use App\Models\Proposal;
use App\Models\Rekening;
use App\Models\User;
use App\Services\DepartmentBudgetService as Budgets;
use App\Services\GroupFundingService as Funding;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Support\LegacySchema;
use Tests\TestCase;

class GroupFundingTest extends TestCase
{
    private Grup $group;

    private Departemen $first;

    private Departemen $second;

    private Rekening $account;

    private User $finance;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        LegacySchema::build();
        Http::preventStrayRequests();
        Http::fake();
        $this->group = Grup::create(['nama_grup' => 'Community']);
        $this->first = Departemen::create(['grup_id' => $this->group->id, 'nama_departemen' => 'Disciples Community']);
        $this->second = Departemen::create(['grup_id' => $this->group->id, 'nama_departemen' => 'KIDS']);
        $this->account = Rekening::create(['nama_rekening' => 'Community Fund', 'saldo' => '20000000']);
        $this->finance = User::create(['name' => 'Finance', 'email' => 'finance@example.test', 'password' => 'test-hash', 'role' => 'keuangan', 'is_active' => 1]);
        $this->employee = User::create(['name' => 'Employee', 'email' => 'employee@example.test', 'password' => 'test-hash', 'role' => 'karyawan', 'is_active' => 1, 'departemen_id' => $this->first->id, 'grup_id' => $this->group->id]);
        $this->actingAs($this->finance);
    }

    private function funding(?int $group = null, string $amount = '20000000', int $year = 2026, ?int $account = null)
    {
        return $this->postJson(route('admin.budget.funding'), ['grup_id' => $group ?? $this->group->id, 'tahun' => $year, 'rekening_id' => $account ?? $this->account->id, 'plafon' => $amount, 'alasan' => 'Pendanaan berdasarkan saldo nyata']);
    }

    private function equal()
    {
        return $this->postJson(route('admin.budget.distribute'), ['grup_id' => $this->group->id, 'tahun' => 2026, 'alasan' => 'Pembagian rata Community']);
    }

    private function allocation(int $department, string $amount)
    {
        return $this->putJson(route('admin.budget.save'), ['departemen_id' => $department, 'tahun' => 2026, 'jumlah' => $amount, 'alasan' => 'Penetapan alokasi departemen']);
    }

    private function proposal(string $amount = '2000000', string $status = 'disetujui_senior_pastor'): Proposal
    {
        return Proposal::create(['user_id' => $this->employee->id, 'departemen_id' => $this->first->id, 'jenis' => 'acara', 'judul' => 'Community activity', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran' => $amount, 'status' => $status]);
    }

    private function payout(Proposal $p, string $amount = '2000000', ?int $account = null)
    {
        return $this->postJson(route('admin.pencairan.store'), ['operation_id' => (string) Str::uuid(), 'proposal_id' => $p->id, 'rekening_id' => $account ?? $this->account->id, 'jumlah_dana' => $amount, 'metode' => 'cash', 'catatan' => 'Pencairan sesuai approval']);
    }

    public function test_funding_is_required_and_twenty_million_is_split_ten_each(): void
    {
        $this->allocation($this->first->id, '1')->assertUnprocessable();
        $this->assertDatabaseCount('budget_departemen', 0);
        $this->funding()->assertRedirect();
        $this->equal()->assertRedirect();
        $this->assertSame(1000000000, app(Budgets::class)->available($this->first->id, 2026));
        $this->assertSame(1000000000, app(Budgets::class)->available($this->second->id, 2026));
        $this->assertSame(2000000000, Budgets::cents($this->account->fresh()->saldo));
        $this->assertSame(2000000000, app(Funding::class)->committed($this->account->id));
        $this->allocation($this->first->id, '10000000.01')->assertUnprocessable();
        $this->assertSame(2000000000, Budgets::cents(DepartmentBudget::sum('jumlah')));
        $this->assertDatabaseHas('activity_logs', ['action' => 'budget.distributed_equally', 'user_id' => $this->finance->id]);
    }

    public function test_same_cash_cannot_back_other_group_or_year_twice(): void
    {
        $other = Grup::create(['nama_grup' => 'Office']);
        $this->funding(amount: '12000000')->assertRedirect();
        $this->funding($other->id, '8000000.01')->assertUnprocessable();
        $this->funding($other->id, '8000000')->assertRedirect();
        $this->funding(amount: '0.01', year: 2027)->assertUnprocessable();
        $this->assertDatabaseCount('group_fundings', 2);
        $this->assertSame(2000000000, app(Funding::class)->committed($this->account->id));
    }

    public function test_existing_limit_is_replaced_not_added_and_cannot_drop_below_allocations(): void
    {
        $this->funding()->assertRedirect();
        $this->funding()->assertRedirect();
        $this->equal()->assertRedirect();
        $this->assertDatabaseCount('group_fundings', 1);
        $this->funding(amount: '19999999.99')->assertUnprocessable();
        $this->funding(amount: '20000000.01')->assertUnprocessable();
        $this->assertSame('20000000.00', GroupFunding::firstOrFail()->plafon);
    }

    public function test_equal_split_preserves_cents_and_replaces_unequal_allocations_atomically(): void
    {
        $this->funding()->assertRedirect();
        $this->allocation($this->first->id, '16000000')->assertRedirect();
        $this->allocation($this->second->id, '4000000')->assertRedirect();
        $this->equal()->assertRedirect();
        $third = Departemen::create(['nama_departemen' => 'Youth', 'grup_id' => $this->group->id]);
        $this->equal()->assertRedirect();
        $values = DepartmentBudget::orderBy('departemen_id')->pluck('jumlah')->map(fn ($v) => Budgets::cents($v))->all();
        $this->assertSame([666666667, 666666667, 666666666], $values);
        $this->assertSame(2000000000, array_sum($values));
    }

    public function test_equal_split_cannot_take_spent_or_reserved_money_and_rolls_back_all_rows(): void
    {
        $this->funding()->assertRedirect();
        $this->allocation($this->first->id, '15000000')->assertRedirect();
        $this->allocation($this->second->id, '5000000')->assertRedirect();
        $this->proposal('11000000');
        $this->equal()->assertUnprocessable();
        $this->assertDatabaseHas('budget_departemen', ['departemen_id' => $this->first->id, 'jumlah' => '15000000']);
        $this->assertDatabaseHas('budget_departemen', ['departemen_id' => $this->second->id, 'jumlah' => '5000000']);
    }

    public function test_payout_and_refund_preserve_backing_without_reducing_group_ceiling(): void
    {
        $this->funding()->assertRedirect();
        $this->equal()->assertRedirect();
        $p = $this->proposal();
        $this->payout($p)->assertRedirect();
        $this->assertSame('20000000.00', GroupFunding::firstOrFail()->plafon);
        $this->assertSame(1800000000, Budgets::cents($this->account->fresh()->saldo));
        $this->assertSame(1800000000, app(Funding::class)->committed($this->account->id));
        $this->allocation($this->first->id, '10000000')->assertRedirect();
        $this->funding(amount: '20000000.01')->assertUnprocessable();
        $this->actingAs($this->employee)->postJson(route('admin.pengembalian_dana.store'), ['operation_id' => (string) Str::uuid(), 'proposal_id' => $p->id, 'jumlah' => '500000', 'metode' => 'cash'])->assertRedirect();
        $refund = PengembalianDana::firstOrFail();
        $this->actingAs($this->finance)->postJson(route('admin.pengembalian_dana.approve', $refund->id), ['rekening_id' => $this->account->id])->assertRedirect();
        $this->assertSame(1850000000, Budgets::cents($this->account->fresh()->saldo));
        $this->assertSame(1850000000, app(Funding::class)->committed($this->account->id));
        $this->assertSame(850000000, app(Budgets::class)->available($this->first->id, 2026));
    }

    public function test_wrong_source_cannot_be_used_for_payout_or_refund_or_history_reassignment(): void
    {
        $this->funding()->assertRedirect();
        $this->equal()->assertRedirect();
        $p = $this->proposal();
        $other = Rekening::create(['nama_rekening' => 'Other source', 'saldo' => '20000000']);
        $this->payout($p, account: $other->id)->assertUnprocessable();
        $this->assertDatabaseCount('pencairan_dana', 0);
        $this->payout($p)->assertRedirect();
        $this->funding(account: $other->id)->assertUnprocessable();
        $refund = PengembalianDana::create(['proposal_id' => $p->id, 'jumlah' => '1000', 'status' => 'pending', 'tanggal_pengembalian' => now(), 'metode' => 'cash']);
        $this->postJson(route('admin.pengembalian_dana.approve', $refund->id), ['rekening_id' => $other->id])->assertUnprocessable();
        $this->assertSame('pending', $refund->fresh()->status);
        $this->assertSame(2000000000, Budgets::cents($other->fresh()->saldo));
    }

    public function test_account_adjustment_and_deletion_cannot_remove_funded_cash(): void
    {
        $this->funding()->assertRedirect();
        $this->putJson(route('admin.rekening.update', $this->account->id), ['nama_rekening' => 'Community Fund', 'saldo' => '19999999.99', 'alasan' => 'Penyesuaian manual rekening'])->assertUnprocessable();
        $this->deleteJson(route('admin.rekening.destroy', $this->account->id))->assertUnprocessable();
        $this->assertSame(2000000000, Budgets::cents($this->account->fresh()->saldo));
    }

    public function test_completed_period_releases_cash_but_reopening_requires_backing(): void
    {
        $this->funding()->assertRedirect();
        $this->equal()->assertRedirect();
        $this->postJson(route('admin.budget.period'), ['tahun' => 2026, 'status' => 'closed', 'alasan' => 'Rekonsiliasi sudah selesai'])->assertRedirect();
        $this->assertSame(0, app(Funding::class)->committed($this->account->id));
        $this->funding(year: 2027)->assertRedirect();
        $this->postJson(route('admin.budget.period'), ['tahun' => 2026, 'status' => 'open', 'alasan' => 'Ingin buka kembali periode'])->assertUnprocessable();
        $this->assertDatabaseHas('budget_periods', ['tahun' => 2026, 'status' => 'closed']);
        $this->assertDatabaseCount('budget_departemen', 2);
    }

    public function test_pending_refund_and_unaccounted_cash_block_period_closure(): void
    {
        $this->funding()->assertRedirect();
        $this->equal()->assertRedirect();
        $p = $this->proposal();
        $this->payout($p)->assertRedirect();
        $close = ['tahun' => 2026, 'status' => 'closed', 'alasan' => 'Percobaan tutup sebelum selesai'];
        $this->postJson(route('admin.budget.period'), $close)->assertUnprocessable();
        $refund = PengembalianDana::create(['proposal_id' => $p->id, 'jumlah' => '2000000', 'status' => 'pending', 'tanggal_pengembalian' => now(), 'metode' => 'cash']);
        $this->postJson(route('admin.budget.period'), $close)->assertUnprocessable();
        $this->postJson(route('admin.pengembalian_dana.approve', $refund->id), ['rekening_id' => $this->account->id])->assertRedirect();
        $this->postJson(route('admin.budget.period'), $close)->assertRedirect();
    }

    public function test_copy_year_requires_real_funding_and_cannot_create_unbacked_allocations(): void
    {
        $this->funding()->assertRedirect();
        $this->equal()->assertRedirect();
        $this->postJson(route('admin.budget.copy'), ['sumber' => 2026, 'tujuan' => 2027, 'alasan' => 'Salin rencana tahun berikut'])->assertUnprocessable();
        $this->assertSame(0, DepartmentBudget::where('tahun', 2027)->count());
    }

    public function test_only_finance_can_assign_sources_or_split_and_pages_show_backing(): void
    {
        $this->funding()->assertRedirect();
        $this->get(route('admin.budget.index', ['tahun' => 2026]))->assertOk()->assertSee('Bagi rata sekarang')->assertSee('Community Fund');
        $this->get(route('admin.rekening.index'))->assertOk()->assertSee('Terikat untuk grup');
        foreach (['karyawan', 'admin', 'departemen_head', 'group_head', 'gembala'] as $role) {
            $u = User::create(['name' => $role, 'email' => $role.'@example.test', 'password' => 'test-hash', 'role' => $role, 'is_active' => 1]);
            $this->actingAs($u);
            $this->funding()->assertForbidden();
            $this->equal()->assertForbidden();
        }
    }

    public function test_legacy_allocations_are_preserved_and_cannot_be_backed_by_insufficient_cash(): void
    {
        DepartmentBudget::create(['departemen_id' => $this->first->id, 'tahun' => 2026, 'jumlah' => '25000000']);
        $this->funding(amount: '25000000')->assertUnprocessable();
        $this->assertDatabaseCount('group_fundings', 0);
        $this->assertDatabaseHas('budget_departemen', ['jumlah' => '25000000']);
    }

    public function test_fractional_payouts_and_refunds_keep_account_and_funding_exact(): void
    {
        $this->funding()->assertRedirect();
        $this->equal()->assertRedirect();
        $p = $this->proposal('0.29');
        $this->payout($p, '0.29')->assertRedirect();
        $this->assertSame('19999999.71', $this->account->fresh()->saldo);
        $this->assertSame(1999999971, app(Funding::class)->committed($this->account->id));
        $refund = PengembalianDana::create(['proposal_id' => $p->id, 'jumlah' => '0.19', 'status' => 'pending', 'tanggal_pengembalian' => now(), 'metode' => 'cash']);
        $this->postJson(route('admin.pengembalian_dana.approve', $refund->id), ['rekening_id' => $this->account->id])->assertRedirect();
        $this->assertSame('19999999.90', $this->account->fresh()->saldo);
        $this->assertSame(1999999990, app(Funding::class)->committed($this->account->id));
    }

    public function test_exhausted_department_requests_transfer_from_same_group_after_existing_approvals(): void
    {
        $this->funding()->assertRedirect();
        $this->equal()->assertRedirect();
        $p = $this->proposal('10000000');
        $this->payout($p, '10000000')->assertRedirect();
        $this->assertSame(0, app(Budgets::class)->available($this->first->id, 2026));
        $approvers = [];
        foreach (['departemen_head', 'group_head', 'gembala'] as $role) {
            $approvers[] = User::create(['name' => $role, 'email' => $role.'@example.test', 'role' => $role, 'is_active' => 1, 'password' => 'test-hash', 'departemen_id' => $this->first->id, 'grup_id' => $this->group->id]);
        }
        $this->actingAs($this->employee)->postJson(route('admin.budget.store'), ['departemen_id' => $this->first->id, 'tahun' => 2026, 'jumlah' => '2000000', 'alasan' => 'Dana habis untuk kegiatan berikutnya'])->assertRedirect();
        $request = BudgetRequest::firstOrFail();
        foreach ($approvers as $approver) {
            $this->actingAs($approver)->postJson(route('admin.budget.decide', $request->id), ['status' => 'disetujui'])->assertRedirect();
        }
        $this->actingAs($this->finance)->postJson(route('admin.budget.fund', $request->id), ['sumber' => [['departemen_id' => $this->second->id, 'jumlah' => '2000000']]])->assertRedirect();
        $this->assertSame(200000000, app(Budgets::class)->available($this->first->id, 2026));
        $this->assertSame(800000000, app(Budgets::class)->available($this->second->id, 2026));
        $this->assertSame('10000000.00', $this->account->fresh()->saldo);
        $this->assertSame('20000000.00', GroupFunding::firstOrFail()->plafon);
        $this->assertSame(2000000000, Budgets::cents(DepartmentBudget::sum('jumlah')));
        $this->assertSame('ditransfer', $request->fresh()->status);
    }
}
