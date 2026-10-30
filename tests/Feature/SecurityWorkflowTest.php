<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ApprovalProposal;
use App\Models\AuthChallenge;
use App\Models\Departemen;
use App\Models\GroupFunding;
use App\Models\Grup;
use App\Models\NotaPenggunaanProposal;
use App\Models\NotificationMessage;
use App\Models\PencairanDana;
use App\Models\PengembalianDana;
use App\Models\Proposal;
use App\Models\Rekening;
use App\Models\User;
use App\Services\DepartmentBudgetService as Budgets;
use App\Services\FinancialBalance;
use App\Services\FonnteService;
use App\Services\GroupFundingService;
use App\Services\NotificationService;
use App\Services\ReportQuery;
use App\Support\Money;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\LegacySchema;
use Tests\TestCase;

class SecurityWorkflowTest extends TestCase
{
    private User $employee;

    private User $finance;

    private User $admin;

    private User $head;

    private User $groupHead;

    private User $pastor;

    private Departemen $it;

    private Departemen $data;

    private object $sender;

    protected function setUp(): void
    {
        parent::setUp();
        LegacySchema::build();
        Http::preventStrayRequests();
        Http::fake();
        Mail::fake();
        Storage::fake('local');
        Storage::fake('public');
        $group = Grup::create(['nama_grup' => 'Office']);
        $this->it = Departemen::create(['grup_id' => $group->id, 'nama_departemen' => 'IT']);
        $this->data = Departemen::create(['grup_id' => $group->id, 'nama_departemen' => 'Data']);
        $this->employee = $this->user('karyawan');
        $this->finance = $this->user('keuangan');
        $this->admin = $this->user('admin');
        $this->head = $this->user('departemen_head');
        $this->groupHead = $this->user('group_head');
        $this->pastor = $this->user('gembala');
        $bank = Rekening::create(['nama_rekening' => 'Bank', 'saldo' => '20000000']);
        app(GroupFundingService::class)->save($group->id, 2026, $bank->id, '20000000', 'Pendanaan awal tes');
        app(Budgets::class)->setBudget($this->it->id, 2026, '5000000');
        app(Budgets::class)->setBudget($this->data->id, 2026, '5000000');
        $this->sender = new class extends FonnteService
        {
            public bool $success = true;

            public array $messages = [];

            public function send(?string $phone, string $message): bool
            {
                $this->messages[] = $message;

                return $this->success;
            }
        };
        app()->instance(FonnteService::class, $this->sender);
    }

    private function user(string $role, ?Departemen $department = null): User
    {
        $department ??= $this->it;
        $n = User::count() + 1;

        return User::create(['name' => $role.' '.$n, 'email' => 'u'.$n.'@example.test', 'nohp' => '081234567'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'role' => $role, 'is_active' => 1, 'password' => Hash::make('Audit-safe-password'), 'departemen_id' => $department->id, 'grup_id' => $department->grup_id]);
    }

    private function proposal(string $status = 'diajukan', ?Departemen $department = null, string $amount = '1000000'): Proposal
    {
        $p = Proposal::create(['user_id' => $this->employee->id, 'departemen_id' => ($department ?? $this->it)->id, 'judul' => 'Test proposal', 'jenis' => 'acara', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran' => $amount, 'status' => $status]);
        $order = 1;
        foreach (['departemen_head' => $this->head, 'group_head' => $this->groupHead, 'gembala' => $this->pastor] as $stage => $user) {
            ApprovalProposal::create(['proposal_id' => $p->id, 'approver_id' => $user->id, 'tahap' => $stage, 'urutan' => $order++, 'status' => $status === 'disetujui_senior_pastor' ? 'disetujui' : 'pending']);
        }

        return $p;
    }

    private function account(): Rekening
    {
        return Rekening::firstOrFail();
    }

    private function paid(string $receipts = '0'): Proposal
    {
        $p = $this->proposal('dicairkan');
        PencairanDana::create(['proposal_id' => $p->id, 'rekening_id' => $this->account()->id, 'jumlah_dana' => '1000000']);
        if ($receipts !== '0') {
            NotaPenggunaanProposal::create(['proposal_id' => $p->id, 'file_nota' => 'test.pdf', 'jumlah' => $receipts]);
        }

        return $p;
    }

    public function test_guest_and_employee_cannot_activate_or_administer_accounts(): void
    {
        $this->employee->update(['is_active' => 0]);
        $this->postJson(route('admin.user.approve', $this->employee->id))->assertUnauthorized();
        $this->employee->update(['is_active' => 1]);
        $this->actingAs($this->employee)->putJson(route('admin.user.update', $this->employee->id), ['role' => 'admin'])->assertForbidden();
        $this->getJson(route('admin.user.pending'))->assertForbidden();
        $this->postJson(route('admin.user.approve', $this->employee->id))->assertForbidden();
        $this->assertSame('karyawan', $this->employee->fresh()->role);
    }

    public function test_bank_and_department_mutations_are_role_restricted(): void
    {
        $account = $this->account();
        $this->actingAs($this->employee)->putJson(route('admin.rekening.update', $account->id), ['nama_rekening' => 'Bank', 'saldo' => '9999999'])->assertForbidden();
        $this->postJson(route('admin.departemen.store'), ['nama_departemen' => 'Forged', 'grup_id' => $this->it->grup_id])->assertForbidden();
        $this->assertSame(2000000000, Budgets::cents($account->fresh()->saldo));
    }

    public function test_inactive_and_password_changed_sessions_are_rejected(): void
    {
        $this->actingAs($this->employee);
        $this->employee->update(['is_active' => 0]);
        $this->getJson(route('admin.budget.index'))->assertUnauthorized();
        $this->employee->update(['is_active' => 1]);
        $this->actingAs($this->employee);
        $this->employee->update(['password' => Hash::make('Replacement-password')]);
        $this->getJson(route('admin.budget.index'))->assertUnauthorized();
    }

    public function test_proposal_approval_checks_owner_stage_order_and_replay(): void
    {
        $p = $this->proposal();
        $flows = $p->approval_flows;
        $this->actingAs($this->employee)->putJson(route('admin.approval.update', $flows[2]->id), ['status' => 'disetujui'])->assertForbidden();
        $this->actingAs($this->pastor)->putJson(route('admin.approval.update', $flows[2]->id), ['status' => 'disetujui'])->assertUnprocessable();
        foreach ([$this->head, $this->groupHead, $this->pastor] as $index => $approver) {
            $this->actingAs($approver)->putJson(route('admin.approval.update', $flows[$index]->id), ['status' => 'disetujui'])->assertRedirect();
        }
        $this->assertSame('disetujui_senior_pastor', $p->fresh()->status);
        $this->putJson(route('admin.approval.update', $flows[2]->id), ['status' => 'disetujui'])->assertStatus(409);
        $this->assertSame(100000000, app(Budgets::class)->reserved($this->it->id, 2026));
    }

    public function test_changes_after_approval_and_cross_department_deletion_are_blocked(): void
    {
        $p = $this->proposal('disetujui_senior_pastor');
        $this->actingAs($this->employee)->putJson(route('admin.proposal.update', $p->id), ['judul' => 'Changed', 'total_anggaran_manual' => '9000000', 'tanggal_pengajuan' => '2026-01-01'])->assertStatus(409);
        $other = $this->proposal('diajukan', $this->data);
        $this->deleteJson(route('admin.proposal.destroy', $other->id))->assertForbidden();
        $this->assertSame(100000000, Budgets::cents($p->fresh()->total_anggaran));
        $this->assertNotNull($other->fresh());
    }

    public function test_revisions_reset_all_approvals_and_item_deletes_are_audited(): void
    {
        $p = $this->proposal('revisi');
        $p->update(['jenis' => 'barang']);
        $p->approval_flows()->get()->each(fn ($f) => $f->update(['status' => $f->tahap === 'gembala' ? 'ditolak' : 'disetujui']));
        $item = $p->items()->create(['nama_barang' => 'Old item', 'anggaran' => '1000']);
        $this->actingAs($this->employee)->putJson(route('admin.proposal.update', $p->id), ['judul' => 'Revised', 'tanggal_pengajuan' => '2026-01-01', 'items' => [['nama_barang' => 'New item', 'anggaran' => '2000']]])->assertRedirect();
        $this->assertSame(3, $p->approval_flows()->where('status', 'pending')->count());
        $this->assertDatabaseHas('activity_logs', ['subject_type' => 'proposal_items', 'subject_id' => (string) $item->id, 'action' => 'deleted']);
    }

    public function test_missing_proposal_approver_prevents_partial_submission(): void
    {
        $this->head->update(['is_active' => 0]);
        $this->actingAs($this->employee)->postJson(route('admin.proposal.store'), ['jenis' => 'acara', 'judul' => 'Incomplete', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '1000'])->assertUnprocessable();
        $this->assertDatabaseCount('proposal', 0);
    }

    public function test_report_scope_and_export_filters_use_same_query(): void
    {
        $own = $this->proposal('disetujui_senior_pastor');
        $other = $this->proposal('disetujui_gh', $this->data);
        $this->actingAs($this->head);
        $response = $this->get(route('admin.laporan.index'));
        $response->assertOk();
        $this->assertSame([$own->id], $response->viewData('data')->pluck('id')->all());
        $request = Request::create('/admin/laporan', 'GET', ['start_date' => '2027-01-01']);
        $request->setUserResolver(fn () => $this->head);
        $this->assertCount(0, app(ReportQuery::class)->query($request)->get());
        $this->getJson(route('admin.laporan.export.excel', ['departemen_id' => $this->data->id]))->assertForbidden();
        $this->get(route('admin.laporan.export.excel'))->assertOk();
        $this->get(route('admin.laporan.export.pdf'))->assertOk();
    }

    public function test_receipts_and_refunds_cannot_exceed_remaining_paid_funds(): void
    {
        $p = $this->paid('800000');
        $this->actingAs($this->employee)->postJson(route('admin.pengembalian_dana.store'), ['operation_id' => (string) Str::uuid(), 'proposal_id' => $p->id, 'jumlah' => '1000000', 'metode' => 'cash'])->assertUnprocessable();
        $this->postJson(route('admin.proposal.uploadNota', $p->id), ['operation_id' => (string) Str::uuid(), 'nota' => [['file' => UploadedFile::fake()->create('nota.pdf', 20, 'application/pdf'), 'jumlah' => '300000']]])->assertUnprocessable();
        $this->postJson(route('admin.pengembalian_dana.store'), ['operation_id' => (string) Str::uuid(), 'proposal_id' => $p->id, 'jumlah' => '200000', 'metode' => 'cash'])->assertRedirect();
        $this->postJson(route('admin.pengembalian_dana.store'), ['operation_id' => (string) Str::uuid(), 'proposal_id' => $p->id, 'jumlah' => '1', 'metode' => 'cash'])->assertUnprocessable();
        $refund = PengembalianDana::firstOrFail();
        $account = Rekening::firstOrFail();
        $before = Budgets::cents($account->saldo);
        $this->actingAs($this->finance)->postJson(route('admin.pengembalian_dana.approve', $refund->id), ['rekening_id' => $account->id])->assertRedirect();
        $this->postJson(route('admin.pengembalian_dana.approve', $refund->id), ['rekening_id' => $account->id])->assertStatus(409);
        $this->assertSame($before + 20000000, Budgets::cents($account->fresh()->saldo));
        $this->assertSame(0, app(FinancialBalance::class)->values($p)['remaining']);
    }

    public function test_refund_and_private_documents_are_scoped_to_owner_or_department(): void
    {
        $p = $this->paid();
        $p->update(['lampiran' => 'proposal_lampiran/test.pdf']);
        Storage::disk('local')->put($p->lampiran, 'private');
        $outsider = $this->user('karyawan', $this->data);
        $this->actingAs($outsider)->postJson(route('admin.pengembalian_dana.store'), ['operation_id' => (string) Str::uuid(), 'proposal_id' => $p->id, 'jumlah' => '10', 'metode' => 'cash'])->assertForbidden();
        $this->getJson(route('admin.documents.show', ['path' => $p->lampiran]))->assertForbidden();
        $this->actingAs($this->employee)->get(route('admin.documents.show', ['path' => $p->lampiran]))->assertOk();
    }

    public function test_admin_can_create_user_but_cannot_activate_unverified_registration(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.user.store'), ['name' => 'Managed user', 'email' => 'managed@example.test', 'nohp' => '081234999999',
            'password' => 'Secure-pass-123', 'role' => 'karyawan', 'departemen_id' => $this->it->id, 'is_active' => 1])->assertRedirect();
        $managed = User::where('email', 'managed@example.test')->firstOrFail();
        $this->assertNotNull($managed->registration_verified_at);
        $pending = $this->user('karyawan');
        $pending->update(['is_active' => 0]);
        $this->postJson(route('admin.user.approve', $pending->id))->assertUnprocessable();
    }

    public function test_registration_otp_activation_and_reset_work_with_separate_purposes(): void
    {
        $input = ['name' => 'Registered', 'email' => 'registered@example.test', 'nohp' => '081234999988', 'password' => 'Secure-pass-123', 'password_confirmation' => 'Secure-pass-123', 'departemen_id' => $this->it->id];
        $this->postJson(route('admin.register.submit'), $input + ['role' => 'keuangan'])->assertUnprocessable();
        $this->post(route('admin.register.submit'), $input + ['role' => 'karyawan'])->assertRedirect();
        preg_match('/\b([0-9]{6})\b/', end($this->sender->messages), $match);
        $otp = $match[1];
        $user = User::where('email', $input['email'])->firstOrFail();
        $this->assertFalse(str_contains(AuthChallenge::firstOrFail()->otp_hash, $otp));
        $this->post(route('admin.register.verify.submit'), ['email' => $user->email, 'otp' => $otp])->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.user.approve', $user->id))->assertRedirect();
        $this->post(route('admin.logout'));
        $this->post(route('admin.forgot.send'), ['email' => $user->email, 'nohp' => $user->nohp])->assertRedirect(route('admin.reset.form', ['email' => $user->email]));
        preg_match('/\b([0-9]{6})\b/', end($this->sender->messages), $match);
        $reset = $match[1];
        $this->post(route('admin.reset.submit'), ['email' => $user->email, 'otp' => $reset, 'password' => 'Replacement-pass-123', 'password_confirmation' => 'Replacement-pass-123'])->assertRedirect(route('admin.login'));
        $this->assertTrue(Hash::check('Replacement-pass-123', $user->fresh()->password));
        $this->postJson(route('admin.reset.submit'), ['email' => $user->email, 'otp' => $reset, 'password' => 'Replacement-pass-123', 'password_confirmation' => 'Replacement-pass-123'])->assertUnprocessable();
    }

    public function test_failed_login_never_flashes_password_and_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.submit'), ['email' => $this->employee->email, 'password' => 'wrong-password'])->assertRedirect();
        }
        $this->assertNull(session('_old_input.password'));
        $this->postJson(route('admin.login.submit'), ['email' => $this->employee->email, 'password' => 'wrong-password'])->assertTooManyRequests();
    }

    public function test_notifications_store_failure_and_retry_without_replaying_finance(): void
    {
        $this->sender->success = false;
        $row = app(NotificationService::class)->queue($this->employee->nohp, 'Private test message', 'Test', $this->employee->id, 'unique-test', immediate: true);
        $this->assertSame('failed', $row->fresh()->status);
        $this->assertStringNotContainsString('Private test message', DB::table('notification_outbox')->where('id', $row->id)->value('message'));
        $this->assertStringNotContainsString('Private test message', ActivityLog::all()->toJson());
        $this->sender->success = true;
        $row->update(['next_attempt_at' => now()->subMinute()]);
        $this->actingAs($this->finance)->post(route('admin.notifications.retry', $row->id))->assertRedirect();
        $this->assertSame('sent', $row->fresh()->status);
        $this->assertDatabaseCount('pencairan_dana', 0);
        $this->postJson(route('admin.notifications.retry', $row->id))->assertStatus(409);
    }

    public function test_budget_reassignment_revision_cancel_and_reversal_preserve_history(): void
    {
        $service = app(Budgets::class);
        $request = $service->submit($this->employee, $this->it, 2026, '1000000', 'Initial request');
        $replacement = $this->user('departemen_head');
        $this->head->update(['is_active' => 0]);
        $this->actingAs($this->admin)->postJson(route('admin.budget.reassign', $request->id), ['approver_id' => $replacement->id, 'alasan' => 'Kepala departemen diganti'])->assertRedirect();
        $service->decide($request->id, $replacement, 'ditolak', 'Perlu rincian');
        $this->actingAs($this->employee)->postJson(route('admin.budget.revise', $request->id), ['jumlah' => '500000', 'alasan' => 'Rincian kebutuhan diperbarui'])->assertRedirect();
        foreach ([$replacement, $this->groupHead, $this->pastor] as $u) {
            $service->decide($request->id, $u, 'disetujui', null);
        }
        $service->fund($request->id, $this->finance, [['departemen_id' => $this->data->id, 'jumlah' => '500000']]);
        $this->actingAs($this->finance)->postJson(route('admin.budget.reverse', $request->id), ['alasan' => 'Kebutuhan dibatalkan pemohon'])->assertRedirect();
        $this->assertSame('dibalik', $request->fresh()->status);
        $this->assertSame(500000000, $service->available($this->it->id, 2026));
        $another = $service->submit($this->employee, $this->it, 2026, '1000', 'Cancel test');
        $this->actingAs($this->employee)->post(route('admin.budget.cancel', $another->id), ['alasan' => 'Tidak lagi dibutuhkan'])->assertRedirect();
        $this->assertSame('dibatalkan', $another->fresh()->status);
    }

    public function test_closed_period_blocks_financial_changes_and_copy_does_not_overwrite(): void
    {
        $newYearBank = Rekening::create(['nama_rekening' => '2027 Fund', 'saldo' => '20000000']);
        app(GroupFundingService::class)->save($this->it->grup_id, 2027, $newYearBank->id, '20000000', 'Pendanaan tahun baru');
        $this->actingAs($this->finance)->postJson(route('admin.budget.copy'), ['sumber' => 2026, 'tujuan' => 2027, 'alasan' => 'Rencana anggaran tahun berikutnya'])->assertRedirect();
        $this->postJson(route('admin.budget.copy'), ['sumber' => 2026, 'tujuan' => 2027, 'alasan' => 'Percobaan menimpa data'])->assertUnprocessable();
        $this->postJson(route('admin.budget.period'), ['tahun' => 2026, 'status' => 'closed', 'alasan' => 'Rekonsiliasi tahun selesai'])->assertRedirect();
        $this->putJson(route('admin.budget.save'), ['departemen_id' => $this->it->id, 'tahun' => 2026, 'jumlah' => '6000000', 'alasan' => 'Ubah setelah penutupan'])->assertUnprocessable();
    }

    public function test_reservations_protect_source_budget_and_final_approval(): void
    {
        $p = $this->proposal('disetujui_senior_pastor', null, '4500000');
        $this->assertSame(50000000, app(Budgets::class)->available($this->it->id, 2026));

        // Set valid approvers for the second department using an isolated fixture head.
        $second = $this->proposal('disetujui_gh', null, '1000000');
        $second->approval_flows()->where('tahap', '!=', 'gembala')->get()->each(fn ($f) => $f->update(['status' => 'disetujui']));
        $last = $second->approval_flows()->where('tahap', 'gembala')->first();
        $this->actingAs($this->pastor)->putJson(route('admin.approval.update', $last->id), ['status' => 'disetujui'])->assertUnprocessable();
    }

    public function test_export_budget_audit_and_notification_pages_render(): void
    {
        $this->actingAs($this->finance)->get(route('admin.budget.export', ['tahun' => 2026]))->assertOk();
        $this->get(route('admin.activity.export'))->assertOk();
        $this->get(route('admin.notifications.index'))->assertOk();
    }

    public function test_no_routes_point_at_missing_methods(): void
    {
        foreach (Route::getRoutes() as $route) {
            $a = $route->getActionName();
            if (! str_contains($a, '@')) {
                continue;
            }[$class,$method] = explode('@', $a, 2);
            $this->assertTrue(class_exists($class) && method_exists($class, $method), $a);
        }
    }

    public function test_payout_retries_are_idempotent_and_changed_payload_is_rejected(): void
    {
        $p = $this->proposal('disetujui_senior_pastor');
        $account = $this->account();
        $key = (string) Str::uuid();
        $data = ['operation_id' => $key, 'proposal_id' => $p->id, 'rekening_id' => $account->id, 'jumlah_dana' => '250000', 'metode' => 'cash', 'catatan' => 'Test payout'];
        $this->actingAs($this->finance)->postJson(route('admin.pencairan.store'), $data)->assertRedirect();
        $this->postJson(route('admin.pencairan.store'), $data)->assertRedirect();
        $this->assertDatabaseCount('pencairan_dana', 1);
        $data['jumlah_dana'] = '500000';
        $this->postJson(route('admin.pencairan.store'), $data)->assertStatus(409);
        $this->assertSame(1975000000, Budgets::cents($account->fresh()->saldo));
    }

    public function test_email_header_injection_and_invalid_money_formats_are_rejected(): void
    {
        $this->postJson(route('admin.forgot.send'), ['email' => "person@example.test\r\nBcc: other@example.test", 'nohp' => '081234567890'])->assertUnprocessable();
        foreach (['-100', '12e3', '1000x50'] as $amount) {
            $this->actingAs($this->employee)->postJson(route('admin.proposal.store'), ['jenis' => 'acara', 'judul' => 'Bad amount', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => $amount])->assertUnprocessable();
        }
        $this->assertSame('1000000.50', Money::normalize('1.000.000,50'));
    }

    public function test_admin_can_return_legacy_incomplete_proposal_for_revision(): void
    {
        $p = $this->proposal('disetujui_mh');
        $this->actingAs($this->admin)->postJson(route('admin.proposal.request-revision', $p->id), ['alasan' => 'Alur lama memerlukan pembaruan'])->assertRedirect();
        $this->assertSame('revisi', $p->fresh()->status);
        $this->actingAs($this->employee)->putJson(route('admin.proposal.update', $p->id), ['judul' => 'Rebuilt workflow', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '1000'])->assertRedirect();
        $this->assertSame(3, $p->approval_flows()->where('status', 'pending')->count());
    }

    public function test_period_closure_and_reversal_reject_unsettled_commitments(): void
    {
        $service = app(Budgets::class);
        $request = $service->submit($this->employee, $this->it, 2026, '1000', 'Test pending');
        $this->actingAs($this->finance)->postJson(route('admin.budget.period'), ['tahun' => 2026, 'status' => 'closed', 'alasan' => 'Attempt closing pending work'])->assertUnprocessable();
        foreach ([$this->head, $this->groupHead, $this->pastor] as $u) {
            $service->decide($request->id, $u, 'disetujui', null);
        }
        $service->fund($request->id, $this->finance, [['departemen_id' => $this->data->id, 'jumlah' => '1000']]);
        $this->proposal('disetujui_senior_pastor', null, '5001000');
        $this->postJson(route('admin.budget.reverse', $request->id), ['alasan' => 'Attempt reversing committed funds'])->assertUnprocessable();
    }

    public function test_disabled_admin_cannot_mutate_and_final_admin_cannot_be_removed(): void
    {
        $this->actingAs($this->admin)->deleteJson(route('admin.user.destroy', $this->admin->id))->assertStatus(409);
        $this->admin->update(['is_active' => 0]);
        $this->getJson(route('admin.user.index'))->assertUnauthorized();
    }

    public function test_all_non_admin_roles_are_denied_user_administration(): void
    {
        foreach ([$this->employee, $this->head, $this->groupHead, $this->pastor, $this->finance] as $user) {
            $this->actingAs($user)->putJson(route('admin.user.update', $user->id), ['role' => 'admin'])->assertForbidden();
            $this->postJson(route('admin.user.approve', $user->id))->assertForbidden();
        }
    }

    public function test_excel_treats_untrusted_formula_text_as_a_string(): void
    {
        $this->employee->update(['name' => '=HYPERLINK("https://example.test","open")']);
        $this->proposal();
        $response = $this->actingAs($this->finance)->get(route('admin.laporan.export.excel'));
        $response->assertOk();
        // Finance export is payout-based; use employee scope to include the proposal owner.
        $response = $this->actingAs($this->head)->get(route('admin.laporan.export.excel'));
        $response->assertOk();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $this->assertIsString($xml);
        $this->assertDoesNotMatchRegularExpression('/<f(?:\s|>)/', $xml);
    }

    public function test_finance_confirmation_is_queued_before_group_approval_without_changing_approval(): void
    {
        $proposal = $this->proposal('disetujui_mh');
        $proposal->approval_flows()->where('tahap', 'departemen_head')->first()->update(['status' => 'disetujui']);
        $url = route('admin.approval.notif-keuangan', $proposal->id);
        $this->actingAs($this->groupHead)->postJson($url)->assertOk()->assertJsonPath('success', true);
        $this->postJson($url)->assertOk();
        $this->assertDatabaseCount('notification_outbox', 1);
        $message = NotificationMessage::firstOrFail();
        $this->assertSame($this->finance->id, (int) $message->user_id);
        $this->assertStringContainsString('memohon konfirmasi ketersediaan dana', $message->message);
        $this->assertSame('disetujui_mh', $proposal->fresh()->status);
        $this->assertSame('pending', $proposal->approval_flows()->where('tahap', 'group_head')->value('status'));
        $this->assertDatabaseCount('pencairan_dana', 0);
    }

    public function test_finance_confirmation_rejects_unassigned_users_wrong_order_and_stale_stage(): void
    {
        $proposal = $this->proposal();
        $url = route('admin.approval.notif-keuangan', $proposal->id);
        $this->actingAs($this->employee)->postJson($url)->assertForbidden();
        $unassigned = $this->user('group_head');
        $this->actingAs($unassigned)->postJson($url)->assertForbidden();
        $this->actingAs($this->groupHead)->postJson($url)->assertStatus(409)->assertJsonStructure(['message']);
        $proposal->update(['status' => 'disetujui_mh']);
        $this->postJson($url)->assertStatus(409);
        $proposal->approval_flows()->where('tahap', 'departemen_head')->first()->update(['status' => 'disetujui']);
        $proposal->update(['status' => 'revisi']);
        $this->postJson($url)->assertStatus(409);
        $this->assertDatabaseCount('notification_outbox', 0);
    }

    public function test_finance_confirmation_reports_missing_finance_and_keeps_final_reminder_separate(): void
    {
        $proposal = $this->proposal('disetujui_senior_pastor');
        $this->finance->update(['is_active' => 0]);
        $url = route('admin.approval.notif-keuangan', $proposal->id);
        $this->actingAs($this->groupHead)->postJson($url)->assertUnprocessable()->assertJsonPath('success', false);
        $this->assertDatabaseCount('notification_outbox', 0);
        $this->finance->update(['is_active' => 1]);
        $this->postJson($url)->assertOk()->assertJsonPath('success', true);
        $message = NotificationMessage::firstOrFail();
        $this->assertSame('Pengingat pencairan keuangan', $message->subject);
        $this->assertStringNotContainsString('memohon konfirmasi', $message->message);
    }

    public function test_proposal_notifications_use_new_submission_and_correct_next_stage_messages(): void
    {
        $this->actingAs($this->employee)->postJson(route('admin.proposal.store'), ['jenis' => 'acara', 'judul' => 'DC Team Night', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '1000000'])->assertRedirect();
        $proposal = Proposal::firstOrFail();
        $notice = NotificationMessage::latest('id')->firstOrFail();
        $this->assertSame($this->head->id, (int) $notice->user_id);
        $this->assertStringContainsString('proposal baru', $notice->message);
        foreach ([$this->head, $this->groupHead] as $index => $approver) {
            $flow = $proposal->approval_flows()->where('approver_id', $approver->id)->firstOrFail();
            $this->actingAs($approver)->putJson(route('admin.approval.update', $flow->id), ['status' => 'disetujui'])->assertRedirect();
            $notice = NotificationMessage::latest('id')->firstOrFail();
            $this->assertSame($index === 0 ? $this->groupHead->id : $this->pastor->id, (int) $notice->user_id);
            $this->assertStringContainsString($index === 0 ? 'telah disetujui Kepala Departemen' : 'telah disetujui Group Head', $notice->message);
            $this->assertStringNotContainsString('proposal revisi', $notice->message);
        }
        $this->assertSame(0, NotificationMessage::where('user_id', $this->employee->id)->count());
    }

    public function test_final_finance_notification_includes_owner_budget_and_pastor_without_disbursing(): void
    {
        $secondFinance = $this->user('keuangan');
        $inactiveFinance = $this->user('keuangan');
        $inactiveFinance->update(['is_active' => 0]);
        $p = $this->proposal(amount: '1000000.50');
        $p->update(['tujuan' => 'Pengadaan mini PC untuk pelayanan data', 'keterangan' => 'Perangkat lama perlu diganti']);
        $before = Budgets::cents($this->account()->saldo);
        foreach ([$this->head, $this->groupHead, $this->pastor] as $approver) {
            $flow = $p->approval_flows()->where('approver_id', $approver->id)->firstOrFail();
            $this->actingAs($approver)->putJson(route('admin.approval.update', $flow->id), ['status' => 'disetujui'])->assertRedirect();
        }
        $messages = NotificationMessage::where('subject', 'Proposal disetujui')->get();
        $this->assertSame([$this->finance->id, $secondFinance->id], $messages->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        foreach ($messages as $notice) {
            foreach (['Proposal #'.$p->id, 'telah disetujui Gembala (Senior Pastor)', 'Judul: Test proposal',
                'Pemohon: '.$this->employee->name, 'Departemen: IT', 'Grup: Office', 'Jenis proposal: Kegiatan / Acara',
                'Tanggal pengajuan: 01/01/2026', 'Total anggaran disetujui: Rp 1.000.000,50', 'Sudah dicairkan: Rp 0,00',
                'Sisa dapat dicairkan: Rp 1.000.000,50', 'Tujuan / alasan: Pengadaan mini PC untuk pelayanan data',
                'Keterangan: Perangkat lama perlu diganti', 'Disetujui Gembala: '.$this->pastor->name,
                'Waktu persetujuan:', 'menu Pencairan Dana', 'belum berarti dana sudah dicairkan'] as $detail) {
                $this->assertStringContainsString($detail, $notice->message);
            }
            $this->assertStringContainsString(User::findOrFail($notice->user_id)->name, $notice->message);
            $this->assertStringNotContainsString('memohon konfirmasi', $notice->message);
        }
        $this->assertDatabaseCount('pencairan_dana', 0);
        $this->assertSame($before, Budgets::cents($this->account()->fresh()->saldo));
        $this->putJson(route('admin.approval.update', $flow->id), ['status' => 'disetujui'])->assertStatus(409);
        $this->assertSame(2, NotificationMessage::where('subject', 'Proposal disetujui')->count());
    }

    public function test_finance_reminder_after_partial_payment_includes_actual_remaining_amount(): void
    {
        $p = $this->proposal('disetujui_senior_pastor', amount: '1000000.50');
        PencairanDana::create(['proposal_id' => $p->id, 'rekening_id' => $this->account()->id, 'jumlah_dana' => '300000.25']);
        $p->update(['status' => 'dicairkan_sebagian']);
        $this->actingAs($this->groupHead)->postJson(route('admin.approval.notif-keuangan', $p->id))->assertOk();
        $notice = NotificationMessage::where('subject', 'Pengingat pencairan keuangan')->firstOrFail();
        foreach (['Pengingat: proposal #'.$p->id, 'Pemohon: '.$this->employee->name, 'Departemen: IT', 'Grup: Office',
            'Total anggaran disetujui: Rp 1.000.000,50', 'Sudah dicairkan: Rp 300.000,25', 'Sisa dapat dicairkan: Rp 700.000,25',
            'Tujuan / alasan: -', 'Disetujui Gembala: '.$this->pastor->name] as $detail) {
            $this->assertStringContainsString($detail, $notice->message);
        }
        $this->postJson(route('admin.approval.notif-keuangan', $p->id))->assertOk();
        $this->assertSame(1, NotificationMessage::where('subject', 'Pengingat pencairan keuangan')->count());
        $this->assertDatabaseCount('pencairan_dana', 1);
    }

    public function test_proposal_notifications_send_rejection_to_owner_with_revision_instructions_and_reason(): void
    {
        foreach ([$this->head, $this->groupHead, $this->pastor] as $index => $approver) {
            $proposal = $this->proposal(['diajukan', 'disetujui_mh', 'disetujui_gh'][$index]);
            $proposal->approval_flows()->where('urutan', '<', $index + 1)->get()->each(fn ($flow) => $flow->update(['status' => 'disetujui']));
            $flow = $proposal->approval_flows()->where('approver_id', $approver->id)->firstOrFail();
            $this->actingAs($approver)->putJson(route('admin.approval.update', $flow->id), ['status' => 'ditolak', 'catatan' => 'Lengkapi rincian konsumsi'])->assertRedirect();
            $notice = NotificationMessage::latest('id')->firstOrFail();
            $this->assertSame($this->employee->id, (int) $notice->user_id);
            $this->assertSame(app(FonnteService::class)->normalizePhone($this->employee->nohp), $notice->target);
            $this->assertStringContainsString('Proposal Anda telah dikembalikan untuk direvisi.', $notice->message);
            $this->assertStringContainsString('Lengkapi rincian konsumsi', $notice->message);
            $this->assertStringContainsString('untuk melakukan revisi', $notice->message);
            $this->assertStringNotContainsString('menunggu persetujuan Anda', $notice->message);
            $this->assertSame('revisi', $proposal->fresh()->status);
        }
        $this->assertDatabaseCount('notification_outbox', 3);
    }

    public function test_proposal_notifications_label_resubmission_as_revision_only_after_owner_revises(): void
    {
        $proposal = $this->proposal('revisi');
        $this->actingAs($this->employee)->putJson(route('admin.proposal.update', $proposal->id), ['judul' => 'DC Team Night updated', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '1000000'])->assertRedirect();
        $notice = NotificationMessage::latest('id')->firstOrFail();
        $this->assertSame($this->head->id, (int) $notice->user_id);
        $this->assertStringContainsString('Proposal revisi', $notice->message);
        $this->assertStringContainsString('telah diajukan kembali', $notice->message);
        $this->assertStringNotContainsString('proposal baru', $notice->message);
        $this->assertSame(3, $proposal->approval_flows()->where('status', 'pending')->count());
    }

    public function test_event_revision_preserves_distinct_event_name_and_legacy_title_fallback(): void
    {
        $this->actingAs($this->employee)->postJson(route('admin.proposal.store'), ['jenis' => 'acara', 'judul' => 'Proposal kegiatan Community', 'judul_acara' => 'DC Team Night', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '1000000'])->assertRedirect();
        $proposal = Proposal::firstOrFail();
        $this->assertSame('DC Team Night', $proposal->judul_acara);
        $this->assertSame('DC Team Night', DB::table('proposal')->where('id', $proposal->id)->value('judul_acara'));
        $proposal->update(['status' => 'revisi']);
        $this->get(route('admin.proposal.edit', $proposal->id))->assertOk()->assertSee('value="DC Team Night"', false);
        $this->putJson(route('admin.proposal.update', $proposal->id), ['judul' => 'Proposal Community diperbarui', 'judul_acara' => 'DC Team Night revisi', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '1500000'])->assertRedirect();
        $this->assertSame('DC Team Night revisi', $proposal->fresh()->judul_acara);
        $this->get(route('admin.proposal.show', $proposal->id))->assertOk()->assertSee('DC Team Night revisi');
        $legacy = $this->proposal('revisi');
        $this->assertNull(DB::table('proposal')->where('id', $legacy->id)->value('judul_acara'));
        $this->get(route('admin.proposal.edit', $legacy->id))->assertOk()->assertSee('value="Test proposal"', false);
        $this->putJson(route('admin.proposal.update', $legacy->id), ['judul' => 'Legacy edited', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '1000'])->assertRedirect();
        $this->assertSame('Test proposal', DB::table('proposal')->where('id', $legacy->id)->value('judul_acara'));
    }

    public function test_final_approval_displays_budget_rejection_on_redirect_and_succeeds_after_funding(): void
    {
        $proposal = $this->proposal('disetujui_gh', null, '6000000');
        $proposal->approval_flows()->where('tahap', '!=', 'gembala')->get()->each(fn ($flow) => $flow->update(['status' => 'disetujui']));
        $last = $proposal->approval_flows()->where('tahap', 'gembala')->firstOrFail();
        $page = route('admin.approval.show', $proposal->id);
        $this->actingAs($this->pastor)->from($page)->put(route('admin.approval.update', $last->id), ['status' => 'disetujui'])->assertRedirect($page)->assertSessionHasErrors('budget');
        $this->get($page)->assertOk()->assertSee('Keputusan belum tersimpan')->assertSee('Persetujuan akhir belum dapat disimpan')->assertSee('Rp 6.000.000,00')->assertSee('Rp 5.000.000,00')->assertSee('Rp 1.000.000,00')->assertSee('Pemeriksaan Budget Departemen');
        $this->assertSame('pending', $last->fresh()->status);
        $this->assertSame('disetujui_gh', $proposal->fresh()->status);
        $this->assertDatabaseCount('notification_outbox', 0);
        app(Budgets::class)->setBudget($this->it->id, 2026, '6000000', 'Tambahan alokasi sudah diproses');
        $this->from($page)->put(route('admin.approval.update', $last->id), ['status' => 'disetujui'])->assertRedirect($page)->assertSessionHas('success');
        $this->assertSame('disetujui', $last->fresh()->status);
        $this->assertSame('disetujui_senior_pastor', $proposal->fresh()->status);
        $this->assertSame(600000000, app(Budgets::class)->reserved($this->it->id, 2026));
    }

    public function test_final_approval_displays_missing_group_funding_instead_of_silent_refresh(): void
    {
        GroupFunding::query()->delete();
        $proposal = $this->proposal('disetujui_gh');
        $proposal->approval_flows()->where('tahap', '!=', 'gembala')->get()->each(fn ($flow) => $flow->update(['status' => 'disetujui']));
        $last = $proposal->approval_flows()->where('tahap', 'gembala')->firstOrFail();
        $page = route('admin.approval.show', $proposal->id);
        $this->actingAs($this->pastor)->from($page)->put(route('admin.approval.update', $last->id), ['status' => 'disetujui'])->assertRedirect($page)->assertSessionHasErrors('pendanaan');
        $this->get($page)->assertOk()->assertSee('Keputusan belum tersimpan')->assertSee('belum ditetapkan');
        $this->assertSame('pending', $last->fresh()->status);
        $this->assertDatabaseCount('notification_outbox', 0);
    }

    public function test_submission_budget_blocks_oversized_events_goods_and_maintenance_before_any_approvals(): void
    {
        app(Budgets::class)->setBudget($this->it->id, 2026, '10000000');
        foreach (['acara', 'barang', 'maintenance'] as $kind) {
            $data = ['jenis' => $kind, 'judul' => 'DC Team Night', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '15000000',
                'items' => [['nama_barang' => 'Kebutuhan pertama', 'anggaran' => '6000000'], ['nama_barang' => 'Kebutuhan kedua', 'anggaran' => '9000000']]];
            $this->actingAs($this->employee)->postJson(route('admin.proposal.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('budget');
        }
        $this->assertDatabaseCount('proposal', 0);
        $this->assertDatabaseCount('approval_proposal', 0);
        $this->assertDatabaseCount('notification_outbox', 0);
        $page = route('admin.proposal.create');
        $this->from($page)->post(route('admin.proposal.store'), ['jenis' => 'acara', 'judul' => 'DC Team Night', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '15000000'])->assertRedirect($page)->assertSessionHasErrors('budget');
        $this->get($page)->assertOk()->assertSee('Proposal belum dapat diajukan')->assertSee('Rp 15.000.000,00')->assertSee('Rp 10.000.000,00')->assertSee('Rp 5.000.000,00');
    }

    public function test_submission_budget_blocks_revision_without_rebuilding_approvals_or_overwriting_data(): void
    {
        $proposal = $this->proposal('revisi');
        $flowIds = $proposal->approval_flows()->pluck('id')->all();
        $this->actingAs($this->employee)->putJson(route('admin.proposal.update', $proposal->id), ['judul' => 'Terlalu besar', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '15000000'])->assertUnprocessable()->assertJsonValidationErrors('budget');
        $this->assertSame('revisi', $proposal->fresh()->status);
        $this->assertSame('Test proposal', $proposal->fresh()->judul);
        $this->assertSame($flowIds, $proposal->approval_flows()->pluck('id')->all());
        $this->assertDatabaseCount('notification_outbox', 0);
        app(Budgets::class)->setBudget($this->it->id, 2026, '15000000', 'Tambahan sudah diproses');
        $this->putJson(route('admin.proposal.update', $proposal->id), ['judul' => 'Sudah cukup', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '15000000'])->assertRedirect();
        $this->assertSame('diajukan', $proposal->fresh()->status);
        $this->assertSame(3, $proposal->approval_flows()->where('status', 'pending')->count());
    }

    public function test_submission_budget_counts_existing_reservations_and_checks_the_submission_year(): void
    {
        $this->proposal('disetujui_senior_pastor', null, '4500000');
        $data = ['jenis' => 'acara', 'judul' => 'Kebutuhan baru', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran_manual' => '1000000'];
        $this->actingAs($this->employee)->postJson(route('admin.proposal.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('budget');
        $data['total_anggaran_manual'] = '500000';
        $this->postJson(route('admin.proposal.store'), $data)->assertRedirect();
        $data['tanggal_pengajuan'] = '2027-01-01';
        $this->postJson(route('admin.proposal.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('pendanaan');
    }

    public function test_password_reset_otp_pages_show_code_form_and_reset_without_link_token(): void
    {
        $this->get(route('admin.forgot.form'))->assertOk()->assertSee('Kirim Kode OTP')->assertDontSee('Kirim Link Reset Password');
        $this->post(route('admin.forgot.send'), ['email' => $this->employee->email, 'nohp' => $this->employee->nohp])->assertRedirect(route('admin.reset.form', ['email' => $this->employee->email]));
        preg_match('/\b([0-9]{6})\b/', end($this->sender->messages), $match);
        $otp = $match[1];
        $page = route('admin.reset.form', ['email' => $this->employee->email]);
        $this->get($page)->assertOk()->assertSee('Kode OTP WhatsApp')->assertSee('name="otp"', false)->assertSee('name="email"', false)->assertDontSee('name="token"', false);
        $data = ['email' => $this->employee->email, 'otp' => '000000', 'password' => 'New-password-123', 'password_confirmation' => 'New-password-123'];
        $this->from($page)->post(route('admin.reset.submit'), $data)->assertRedirect($page)->assertSessionHasErrors('otp');
        $this->assertNull(session('_old_input.otp'));
        $this->assertNull(session('_old_input.password'));
        $data['otp'] = $otp;
        $this->post(route('admin.reset.submit'), $data)->assertRedirect(route('admin.login'));
        $this->assertTrue(Hash::check('New-password-123', $this->employee->fresh()->password));
        $this->postJson(route('admin.reset.submit'), $data)->assertUnprocessable();
    }

    public function test_password_reset_otp_resend_cancels_failed_old_messages_and_expired_code_is_rejected(): void
    {
        $this->sender->success = false;
        $input = ['email' => $this->employee->email, 'nohp' => $this->employee->nohp];
        $this->post(route('admin.forgot.send'), $input)->assertRedirect();
        $old = NotificationMessage::latest('id')->firstOrFail();
        $this->assertSame('failed', $old->status);
        $this->post(route('admin.forgot.send'), $input)->assertRedirect();
        $this->assertSame('expired', $old->fresh()->status);
        $attempts = count($this->sender->messages);
        $this->assertFalse(app(NotificationService::class)->deliver($old->id));
        $this->assertCount($attempts, $this->sender->messages);
        preg_match('/\b([0-9]{6})\b/', end($this->sender->messages), $match);
        $data = ['email' => $this->employee->email, 'otp' => $match[1], 'password' => 'New-password-123', 'password_confirmation' => 'New-password-123'];
        $challenge = AuthChallenge::where('user_id', $this->employee->id)->where('purpose', 'password_reset')->firstOrFail();
        $this->assertTrue(Hash::check($data['otp'], $challenge->otp_hash));
        $challenge->update(['expires_at' => now()->subSecond()]);
        $this->postJson(route('admin.reset.submit'), $data)->assertUnprocessable();
        $this->assertTrue(Hash::check('Audit-safe-password', $this->employee->fresh()->password));
    }

    public function test_reset_delivery_matches_equivalent_phone_formats_but_rejects_different_number(): void
    {
        $canonical = app(FonnteService::class)->normalizePhone($this->employee->nohp);
        foreach (['+'.$canonical, '62 '.substr($canonical, 2)] as $phone) {
            $this->post(route('admin.forgot.send'), ['email' => $this->employee->email, 'nohp' => $phone])->assertRedirect();
        }
        $this->assertDatabaseCount('notification_outbox', 2);
        $this->assertSame(2, NotificationMessage::where('status', 'sent')->count());
        $this->assertSame($canonical, NotificationMessage::latest('id')->firstOrFail()->target);
        $this->post(route('admin.forgot.send'), ['email' => $this->employee->email, 'nohp' => $canonical.'9'])->assertRedirect();
        $this->assertDatabaseCount('notification_outbox', 2);
        $this->assertNotNull(session('info'));
        $this->assertNull(session('success'));
    }

    public function test_reset_delivery_records_missing_token_and_safe_provider_failure_without_public_account_disclosure(): void
    {
        app()->instance(FonnteService::class, new FonnteService);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        config(['services.fonnte.token' => '', 'services.fonnte.url' => 'https://api.fonnte.com/send']);
        $input = ['email' => $this->employee->email, 'nohp' => $this->employee->nohp];
        $this->post(route('admin.forgot.send'), $input)->assertRedirect();
        $first = NotificationMessage::latest('id')->firstOrFail();
        $this->assertSame('failed', $first->status);
        $this->assertStringContainsString('FONNTE_TOKEN belum tersedia', $first->last_error);
        Http::assertNothingSent();
        $publicMessage = session('info');
        config(['services.fonnte.token' => 'unit-test-credential-not-real']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'device disconnected; unit-test-credential-not-real'], 200)]);
        $this->post(route('admin.forgot.send'), $input)->assertRedirect();
        $latest = NotificationMessage::latest('id')->firstOrFail();
        $this->assertSame('failed', $latest->status);
        $this->assertStringContainsString('Perangkat WhatsApp', $latest->last_error);
        $this->assertStringNotContainsString('unit-test-credential-not-real', $latest->last_error);
        Http::assertSentCount(1);
        $this->post(route('admin.forgot.send'), ['email' => 'unknown@example.test', 'nohp' => $this->employee->nohp])->assertRedirect();
        $this->assertSame($publicMessage, session('info'));
        $this->assertDatabaseCount('notification_outbox', 2);
    }

    public function test_reset_delivery_diagnostic_is_read_only_and_does_not_send_otp(): void
    {
        config(['services.fonnte.token' => '']);
        $this->artisan('auth:diagnose-reset', ['email' => $this->employee->email, '--phone' => '+'.app(FonnteService::class)->normalizePhone($this->employee->nohp)])
            ->expectsOutput('Email ditemukan: Ya')->expectsOutput('Akun aktif: Ya')
            ->expectsOutput('Nomor masukan cocok setelah normalisasi: Ya')
            ->expectsOutput('FONNTE_TOKEN tersedia di konfigurasi aktif: Tidak')->assertExitCode(0);
        $this->assertDatabaseCount('auth_challenges', 0);
        $this->assertDatabaseCount('notification_outbox', 0);
        Http::assertNothingSent();
    }
}
