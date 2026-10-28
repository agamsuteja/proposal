<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BudgetRequest;
use App\Models\Departemen;
use App\Models\DepartmentBudget;
use App\Models\Grup;
use App\Models\Proposal;
use App\Models\Rekening;
use App\Models\User;
use App\Services\DepartmentBudgetService as Budgets;
use App\Services\GroupFundingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\LegacySchema;
use Tests\TestCase;

class DepartmentBudgetTest extends TestCase
{
    private User $employee;

    private User $head;

    private User $groupHead;

    private User $pastor;

    private User $finance;

    private Departemen $it;

    private Departemen $data;

    private Budgets $budgets;

    protected function setUp(): void
    {
        parent::setUp();
        LegacySchema::build();
        $office = Grup::create(['nama_grup' => 'Office']);
        $this->it = Departemen::create(['grup_id' => $office->id, 'nama_departemen' => 'IT']);
        $this->data = Departemen::create(['grup_id' => $office->id, 'nama_departemen' => 'Data Manager']);
        $this->employee = $this->user('karyawan', $this->it);
        $this->head = $this->user('departemen_head', $this->it);
        $this->groupHead = $this->user('group_head', $this->it);
        $this->pastor = $this->user('gembala', $this->it);
        $this->finance = $this->user('keuangan', $this->it);
        $account = Rekening::create(['nama_rekening' => 'Office Fund', 'saldo' => '20000000']);
        app(GroupFundingService::class)->save($office->id, 2026, $account->id, '20000000', 'Pendanaan awal tes');
        $this->budgets = app(Budgets::class);
        $this->budgets->setBudget($this->it->id, 2026, '5000000.00');
        $this->budgets->setBudget($this->data->id, 2026, '5000000.00');
        Http::fake();
    }

    private function user(string $role, Departemen $department): User
    {
        return User::create(['name' => $role, 'email' => uniqid().'@example.test', 'password' => 'test-hash',
            'role' => $role, 'is_active' => 1, 'departemen_id' => $department->id, 'grup_id' => $department->grup_id]);
    }

    private function requestBudget(string $amount = '1000000.00'): BudgetRequest
    {
        $this->actingAs($this->employee)->postJson(route('admin.budget.store'), ['departemen_id' => $this->it->id,
            'tahun' => 2026, 'jumlah' => $amount, 'alasan' => 'Kebutuhan tambahan IT'])->assertRedirect();

        return BudgetRequest::latest('id')->firstOrFail();
    }

    private function approve(BudgetRequest $request): void
    {
        foreach ([$this->head, $this->groupHead, $this->pastor] as $user) {
            $this->actingAs($user)->postJson(route('admin.budget.decide', $request->id), ['status' => 'disetujui'])->assertRedirect();
        }
    }

    public function test_group_total_is_ten_million_and_transfer_preserves_it(): void
    {
        $this->actingAs($this->finance)->get(route('admin.budget.index', ['tahun' => 2026]))->assertOk()->assertSee('10.000.000,00');
        $request = $this->requestBudget();
        $this->actingAs($this->employee)->get(route('admin.budget.requests', ['tahun' => 2026]))->assertOk()->assertSee('Ajukan tambahan budget');
        $this->approve($request);
        $this->actingAs($this->finance)->postJson(route('admin.budget.fund', $request->id), [
            'sumber' => [['departemen_id' => $this->data->id, 'jumlah' => '1000000.00']],
        ])->assertRedirect();
        $this->assertSame(600000000, Budgets::cents(DepartmentBudget::where('departemen_id', $this->it->id)->value('jumlah')));
        $this->assertSame(400000000, $this->budgets->available($this->data->id, 2026));
        $this->assertSame(1000000000, Budgets::cents(DepartmentBudget::sum('jumlah')));
        $this->assertSame('ditransfer', $request->fresh()->status);
        $this->assertDatabaseHas('budget_transfers', ['sumber_id' => $this->data->id, 'tujuan_id' => $this->it->id, 'user_id' => $this->finance->id]);
        $this->actingAs($this->finance)->postJson(route('admin.budget.fund', $request->id), [
            'sumber' => [['departemen_id' => $this->data->id, 'jumlah' => '1000000.00']],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('budget_transfers', 1);
    }

    public function test_only_assigned_approver_can_decide_in_order_and_finance_waits(): void
    {
        $request = $this->requestBudget();
        foreach ([$this->employee, $this->groupHead, $this->pastor, $this->finance] as $user) {
            $this->actingAs($user)->postJson(route('admin.budget.decide', $request->id), ['status' => 'disetujui'])->assertForbidden();
        }
        $this->actingAs($this->finance)->postJson(route('admin.budget.fund', $request->id), [
            'sumber' => [['departemen_id' => $this->data->id, 'jumlah' => '1000000']],
        ])->assertUnprocessable();
        $this->actingAs($this->head)->postJson(route('admin.budget.decide', $request->id), ['status' => 'ditolak', 'catatan' => 'Perlu revisi'])->assertRedirect();
        $this->assertSame('ditolak', $request->fresh()->status);
        $this->actingAs($this->groupHead)->postJson(route('admin.budget.decide', $request->id), ['status' => 'disetujui'])->assertUnprocessable();
        $this->requestBudget();
        $this->assertDatabaseCount('budget_requests', 2);
    }

    public function test_multiple_sources_are_atomic_and_cross_group_or_overdraft_is_rejected(): void
    {
        $third = Departemen::create(['grup_id' => $this->it->grup_id, 'nama_departemen' => 'HR']);
        $other = Departemen::create(['grup_id' => Grup::create(['nama_grup' => 'Other'])->id, 'nama_departemen' => 'Other']);
        $this->budgets->setBudget($third->id, 2026, '2000000');
        $bank = Rekening::create(['nama_rekening' => 'Other Fund', 'saldo' => '2000000']);
        app(GroupFundingService::class)->save($other->grup_id, 2026, $bank->id, '2000000', 'Pendanaan grup lain');
        $this->budgets->setBudget($other->id, 2026, '2000000');
        $request = $this->requestBudget('6000000');
        $this->approve($request);
        foreach ([
            [['departemen_id' => $this->data->id, 'jumlah' => '6000000']],
            [['departemen_id' => $this->data->id, 'jumlah' => '5000000'], ['departemen_id' => $other->id, 'jumlah' => '1000000']],
            [['departemen_id' => $this->it->id, 'jumlah' => '6000000']],
            [['departemen_id' => $this->data->id, 'jumlah' => '1000000']],
            [['departemen_id' => $this->data->id, 'jumlah' => '3000000'], ['departemen_id' => $this->data->id, 'jumlah' => '3000000']],
        ] as $sources) {
            $this->actingAs($this->finance)->postJson(route('admin.budget.fund', $request->id), ['sumber' => $sources])->assertUnprocessable();
            $this->assertDatabaseCount('budget_transfers', 0);
            $this->assertSame(500000000, $this->budgets->available($this->data->id, 2026));
        }
        $this->actingAs($this->finance)->postJson(route('admin.budget.fund', $request->id), ['sumber' => [
            ['departemen_id' => $this->data->id, 'jumlah' => '4000000'], ['departemen_id' => $third->id, 'jumlah' => '2000000'],
        ]])->assertRedirect();
        $this->assertSame(100000000, $this->budgets->available($this->data->id, 2026));
        $this->assertSame(0, $this->budgets->available($third->id, 2026));
        $this->assertSame(1100000000, $this->budgets->available($this->it->id, 2026));
    }

    public function test_department_access_and_finance_permissions_are_enforced(): void
    {
        $this->actingAs($this->employee)->get(route('admin.budget.index', ['tahun' => 2026]))->assertOk()->assertSee('IT')->assertDontSee('Data Manager');
        $this->actingAs($this->employee)->postJson(route('admin.budget.store'), ['departemen_id' => $this->data->id,
            'tahun' => 2026, 'jumlah' => '1', 'alasan' => 'No access'])->assertNotFound();
        $this->actingAs($this->employee)->putJson(route('admin.budget.save'), ['departemen_id' => $this->it->id, 'tahun' => 2026, 'jumlah' => '1'])->assertForbidden();
        $this->actingAs($this->employee)->get(route('admin.activity.index'))->assertForbidden();
        $this->actingAs($this->finance)->get(route('admin.activity.index'))->assertOk();
    }

    public function test_missing_approvers_block_submission_without_partial_records(): void
    {
        $this->pastor->update(['is_active' => 0]);
        $this->actingAs($this->employee)->postJson(route('admin.budget.store'), ['departemen_id' => $this->it->id,
            'tahun' => 2026, 'jumlah' => '1', 'alasan' => 'Test'])->assertUnprocessable();
        $this->assertDatabaseCount('budget_requests', 0);
        $this->assertDatabaseCount('budget_approvals', 0);
    }

    public function test_spending_reduces_transferable_budget_and_accepted_refunds_restore_it(): void
    {
        $proposal = DB::table('proposal')->insertGetId(['departemen_id' => $this->data->id, 'user_id' => $this->employee->id,
            'judul' => 'Existing', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran' => '4000000', 'status' => 'dicairkan']);
        DB::table('pencairan_dana')->insert(['proposal_id' => $proposal, 'rekening_id' => 1, 'jumlah_dana' => '4000000']);
        DB::table('pengembalian_dana')->insert(['proposal_id' => $proposal, 'jumlah' => '1000000', 'status' => 'pending', 'tanggal_pengembalian' => now(), 'metode' => 'cash']);
        $this->assertSame(100000000, $this->budgets->available($this->data->id, 2026));
        $this->actingAs($this->finance)->putJson(route('admin.budget.save'), ['departemen_id' => $this->data->id, 'tahun' => 2026, 'jumlah' => '3000000'])->assertUnprocessable();
        $request = $this->requestBudget('2000000');
        $this->approve($request);
        $source = ['sumber' => [['departemen_id' => $this->data->id, 'jumlah' => '2000000']]];
        $this->actingAs($this->finance)->postJson(route('admin.budget.fund', $request->id), $source)->assertUnprocessable();
        DB::table('pengembalian_dana')->update(['status' => 'diterima']);
        $this->assertSame(200000000, $this->budgets->available($this->data->id, 2026));
        $this->actingAs($this->finance)->postJson(route('admin.budget.fund', $request->id), $source)->assertRedirect();
    }

    public function test_disbursement_cannot_exceed_department_budget(): void
    {
        $proposal = DB::table('proposal')->insertGetId(['departemen_id' => $this->it->id, 'user_id' => $this->employee->id,
            'judul' => 'Test', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran' => '6000000', 'status' => 'disetujui_senior_pastor']);
        $account = Rekening::firstOrFail()->id;
        $data = ['operation_id' => (string) Str::uuid(), 'proposal_id' => $proposal, 'rekening_id' => $account, 'jumlah_dana' => '6000000', 'metode' => 'cash', 'catatan' => 'Test'];
        $this->actingAs($this->employee)->postJson(route('admin.pencairan.store'), $data)->assertForbidden();
        $this->actingAs($this->finance)->post(route('admin.pencairan.store'), $data)->assertSessionHasErrors('jumlah_dana');
        $this->assertDatabaseCount('pencairan_dana', 0);
        $this->assertSame(2000000000, Budgets::cents(DB::table('rekening')->value('saldo')));
        $data['jumlah_dana'] = '1000000';
        $this->actingAs($this->finance)->post(route('admin.pencairan.store'), $data)->assertSessionHas('success');
        $this->assertDatabaseCount('pencairan_dana', 1);
        $this->assertSame(-100000000, $this->budgets->available($this->it->id, 2026));
        Http::assertNothingSent();
    }

    public function test_audit_records_actor_changes_and_requests_without_passwords(): void
    {
        $this->actingAs($this->finance)->put(route('admin.budget.save'), ['departemen_id' => $this->it->id, 'tahun' => 2026, 'jumlah' => '5500000', 'alasan' => 'Penyesuaian berdasarkan kebutuhan'])->assertRedirect();
        $log = ActivityLog::where('subject_type', 'budget_departemen')->where('action', 'updated')->latest('id')->firstOrFail();
        $this->assertSame($this->finance->id, $log->user_id);
        $this->assertSame(500000000, Budgets::cents($log->before['jumlah']));
        $this->assertSame(550000000, Budgets::cents($log->after['jumlah']));
        $this->assertDatabaseHas('activity_logs', ['user_id' => $this->finance->id, 'route' => 'admin.budget.save', 'action' => 'request']);
        $this->employee->update(['password' => 'never-log-this-secret']);
        $this->assertStringNotContainsString('never-log-this-secret', ActivityLog::all()->toJson());
        $this->assertStringNotContainsString('test-hash', ActivityLog::all()->toJson());
    }

    public function test_failed_actions_are_logged_and_financial_history_cannot_be_reassigned(): void
    {
        $this->actingAs($this->employee)->putJson(route('admin.budget.save'), ['departemen_id' => $this->it->id, 'tahun' => 2026, 'jumlah' => '1'])->assertForbidden();
        $this->assertDatabaseHas('activity_logs', ['action' => 'request', 'response_status' => 403, 'user_id' => $this->employee->id]);
        $proposal = Proposal::create(['departemen_id' => $this->it->id, 'user_id' => $this->employee->id,
            'judul' => 'History', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran' => '100', 'status' => 'dicairkan']);
        DB::table('pencairan_dana')->insert(['proposal_id' => $proposal->id, 'rekening_id' => 1, 'jumlah_dana' => '100']);
        try {
            $proposal->update(['departemen_id' => $this->data->id]);
            $this->fail('Financial history must remain in the original department');
        } catch (ValidationException $expected) {
            $this->assertSame($this->it->id, $proposal->fresh()->departemen_id);
        }
    }

    public function test_years_are_isolated_and_decimal_amounts_are_exact(): void
    {
        $this->assertSame(0, $this->budgets->available($this->it->id, 2027));
        $request = $this->requestBudget('0.29');
        $this->approve($request);
        $this->actingAs($this->finance)->postJson(route('admin.budget.fund', $request->id), [
            'sumber' => [['departemen_id' => $this->data->id, 'jumlah' => '0.29']],
        ])->assertRedirect();
        $this->assertSame(500000029, $this->budgets->available($this->it->id, 2026));
        $this->assertSame(499999971, $this->budgets->available($this->data->id, 2026));
        $this->actingAs($this->finance)->putJson(route('admin.budget.save'), ['departemen_id' => $this->it->id, 'tahun' => 2026, 'jumlah' => '6000000', 'alasan' => 'Penyesuaian disetujui keuangan'])->assertRedirect();
    }
}
