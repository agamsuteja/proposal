<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BudgetRequest;
use App\Models\Departemen;
use App\Models\DepartmentBudget;
use App\Models\Grup;
use App\Models\NotificationMessage;
use App\Models\Proposal;
use App\Models\Rekening;
use App\Models\User;
use App\Services\DepartmentBudgetService as Budgets;
use App\Services\GroupFundingService;
use Barryvdh\DomPDF\Facade\Pdf;
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

    private function assertBudgetMessageDetails(string $message, string $amount = 'Rp 700.000,50', string $reason = 'Kebutuhan tambahan IT'): void
    {
        foreach (['Pemohon: '.$this->employee->name, 'Departemen: IT', 'Grup: Office', 'Tahun budget: 2026',
            'Tambahan yang diajukan: '.$amount, 'Alasan pengajuan: '.$reason] as $detail) {
            $this->assertStringContainsString($detail, $message);
        }
    }

    public function test_budget_notifications_include_request_details_and_instructions_for_each_stage(): void
    {
        $request = $this->requestBudget('700000.50');
        foreach ([$this->head, $this->groupHead, $this->pastor, $this->finance] as $i => $recipient) {
            $notice = NotificationMessage::latest('id')->firstOrFail();
            $this->assertSame($recipient->id, (int) $notice->user_id);
            $this->assertBudgetMessageDetails($notice->message);
            $this->assertStringContainsString('tambahan budget #'.$request->id, $notice->message);
            $this->assertStringContainsString(['Kepala Departemen (MH)', 'Kepala Grup (GH)', 'persetujuan Gembala', 'menunggu transfer alokasi oleh Keuangan'][$i], $notice->message);
            $this->assertStringContainsString($i < 3 ? 'memberikan keputusan' : 'satu atau beberapa departemen sumber dalam grup yang sama', $notice->message);
            if ($i < 3) {
                $this->actingAs($recipient)->postJson(route('admin.budget.decide', $request->id), ['status' => 'disetujui'])->assertRedirect();
            }
        }
        $this->artisan('workflow:remind')->assertExitCode(0);
        $notice = NotificationMessage::latest('id')->firstOrFail();
        $this->assertSame($this->finance->id, (int) $notice->user_id);
        $this->assertBudgetMessageDetails($notice->message);
        $this->assertStringContainsString('Pengingat pengajuan', $notice->message);
        $this->assertStringContainsString('bukan pencairan uang dari rekening', $notice->message);
    }

    public function test_budget_transfer_message_lists_all_source_departments_and_finance_actor(): void
    {
        $third = Departemen::create(['nama_departemen' => 'Administrasi', 'grup_id' => $this->it->grup_id]);
        $this->budgets->setBudget($third->id, 2026, '1000000');
        $request = $this->requestBudget('700000.50');
        $this->approve($request);
        $this->actingAs($this->finance)->postJson(route('admin.budget.fund', $request->id), ['sumber' => [
            ['departemen_id' => $this->data->id, 'jumlah' => '300000.25'],
            ['departemen_id' => $third->id, 'jumlah' => '400000.25'],
        ]])->assertRedirect();
        $notice = NotificationMessage::latest('id')->firstOrFail();
        $this->assertSame($this->employee->id, (int) $notice->user_id);
        $this->assertBudgetMessageDetails($notice->message);
        $this->assertStringContainsString('- Data Manager: Rp 300.000,25', $notice->message);
        $this->assertStringContainsString('- Administrasi: Rp 400.000,25', $notice->message);
        $this->assertStringContainsString('Diproses oleh Keuangan: '.$this->finance->name, $notice->message);
        $this->assertStringContainsString('bukan pencairan uang dari rekening', $notice->message);
        $this->assertSame(570000050, Budgets::cents(DepartmentBudget::where('departemen_id', $this->it->id)->value('jumlah')));
    }

    public function test_budget_rejection_and_revised_reminders_use_current_reason_and_owner_recipient(): void
    {
        $request = $this->requestBudget('700000.50');
        $this->actingAs($this->head)->postJson(route('admin.budget.decide', $request->id), ['status' => 'ditolak', 'catatan' => 'Lengkapi rincian konsumsi'])->assertRedirect();
        $notice = NotificationMessage::latest('id')->firstOrFail();
        $this->assertSame($this->employee->id, (int) $notice->user_id);
        $this->assertBudgetMessageDetails($notice->message);
        $this->assertStringContainsString('Ditolak oleh: '.$this->head->name.' — Kepala Departemen (MH)', $notice->message);
        $this->assertStringContainsString('Catatan penolakan: Lengkapi rincian konsumsi', $notice->message);
        $this->assertStringContainsString('perbaiki pengajuan', $notice->message);
        $this->assertStringNotContainsString('memberikan keputusan', $notice->message);
        $this->actingAs($this->employee)->postJson(route('admin.budget.revise', $request->id), ['jumlah' => '800000.25',
            'alasan' => "Tambahan konsumsi\nuntuk peserta https://example.test/detail"])->assertRedirect();
        $notice = NotificationMessage::latest('id')->firstOrFail();
        $this->assertSame($this->head->id, (int) $notice->user_id);
        $this->assertBudgetMessageDetails($notice->message, 'Rp 800.000,25', 'Tambahan konsumsi untuk peserta [tautan dihapus]');
        $this->assertStringNotContainsString('Kebutuhan tambahan IT', $notice->message);
        $this->artisan('workflow:remind')->assertExitCode(0);
        $notice = NotificationMessage::latest('id')->firstOrFail();
        $this->assertBudgetMessageDetails($notice->message, 'Rp 800.000,25', 'Tambahan konsumsi untuk peserta [tautan dihapus]');
        $this->assertStringContainsString('Pengingat pengajuan', $notice->message);
    }

    public function test_budget_request_pdf_download_renders_approvals_sources_and_empty_year(): void
    {
        $request = $this->requestBudget('700000.50');
        $this->approve($request);
        $this->actingAs($this->finance)->postJson(route('admin.budget.fund', $request->id), ['sumber' => [
            ['departemen_id' => $this->data->id, 'jumlah' => '700000.50'],
        ]])->assertRedirect();
        $this->actingAs($this->employee)->get(route('admin.budget.requests', ['tahun' => 2026]))
            ->assertOk()->assertSee(route('admin.budget.requests.pdf', ['tahun' => 2026]), false);
        $response = $this->get(route('admin.budget.requests.pdf', ['tahun' => 2026]));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('laporan-tambahan-budget-2026.pdf', $response->headers->get('content-disposition'));
        $html = view('backend.budget.requests_pdf', ['requests' => collect([$request->fresh()->load(['user', 'departemen.grup', 'approvals.approver', 'transfers.sumber', 'transfers.user', 'reversal'])]),
            'year' => 2026, 'printedBy' => $this->employee->name, 'printedAt' => '30/10/2026 12:00 WIB'])->render();
        foreach (['Kebutuhan tambahan IT', 'Data Manager', 'Rp 700.000,50', 'Sudah ditransfer', 'Kepala Departemen (MH)', 'Kepala Grup (GH)', 'Gembala', 'keuangan'] as $detail) {
            $this->assertStringContainsString($detail, $html);
        }
        $this->get(route('admin.budget.requests.pdf', ['tahun' => 2027]))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->getJson(route('admin.budget.requests.pdf', ['tahun' => 'invalid']))->assertUnprocessable();
    }

    public function test_budget_request_pdf_uses_role_scope_year_and_all_pages(): void
    {
        for ($i = 0; $i < 16; $i++) {
            BudgetRequest::create(['user_id' => $this->employee->id, 'departemen_id' => $this->it->id, 'tahun' => 2026,
                'jumlah' => '100', 'alasan' => 'Audit scope '.$i, 'status' => 'pending']);
        }
        BudgetRequest::create(['user_id' => $this->employee->id, 'departemen_id' => $this->data->id, 'tahun' => 2026,
            'jumlah' => '100', 'alasan' => 'Departemen lain dalam grup', 'status' => 'pending']);
        $otherGroup = Grup::create(['nama_grup' => 'Community']);
        $otherDepartment = Departemen::create(['grup_id' => $otherGroup->id, 'nama_departemen' => 'KIDS']);
        BudgetRequest::create(['user_id' => $this->employee->id, 'departemen_id' => $otherDepartment->id, 'tahun' => 2026,
            'jumlah' => '100', 'alasan' => 'Grup lain', 'status' => 'pending']);
        BudgetRequest::create(['user_id' => $this->employee->id, 'departemen_id' => $this->it->id, 'tahun' => 2027,
            'jumlah' => '100', 'alasan' => 'Tahun lain', 'status' => 'pending']);
        $admin = $this->user('admin', $this->it);
        foreach ([[$this->employee, 16], [$this->head, 16], [$this->groupHead, 17], [$this->pastor, 18], [$this->finance, 18], [$admin, 18]] as [$actor, $expected]) {
            $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
            Pdf::shouldReceive('loadView')->once()->with('backend.budget.requests_pdf', \Mockery::on(function ($data) use ($expected, $actor) {
                $this->assertCount($expected, $data['requests']);
                $this->assertSame([2026], $data['requests']->pluck('tahun')->unique()->values()->all());
                $this->assertSame($actor->name, $data['printedBy']);
                return true;
            }))->andReturn($pdf);
            $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
            $pdf->shouldReceive('download')->once()->with('laporan-tambahan-budget-2026.pdf')->andReturn(response('Scoped PDF'));
            $this->actingAs($actor)->get(route('admin.budget.requests.pdf', ['tahun' => 2026]))->assertOk();
        }
        auth()->logout();
        $this->getJson(route('admin.budget.requests.pdf', ['tahun' => 2026]))->assertUnauthorized();
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
