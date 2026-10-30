<?php

namespace Tests\Feature;

use App\Models\BudgetPeriod;
use App\Models\Departemen;
use App\Models\GroupFunding;
use App\Models\Grup;
use App\Models\PengembalianDana;
use App\Models\Proposal;
use App\Models\Rekening;
use App\Models\User;
use App\Services\DepartmentBudgetService as Budgets;
use App\Services\GroupFundingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\LegacySchema;
use Tests\TestCase;

class PayoutWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        LegacySchema::build();
        Http::preventStrayRequests();
        Http::fake();
        Storage::fake('local');
        $group = Grup::create(['nama_grup' => 'Office']);
        $department = Departemen::create(['nama_departemen' => 'IT', 'grup_id' => $group->id]);
        foreach (['admin', 'keuangan', 'karyawan'] as $role) {
            User::create(['name' => $role, 'email' => $role.'@example.test', 'password' => 'test-hash', 'role' => $role,
                'is_active' => 1, 'departemen_id' => $department->id, 'grup_id' => $group->id]);
        }
        $account = Rekening::create(['nama_rekening' => 'Bank', 'saldo' => '20000000']);
        app(GroupFundingService::class)->save($group->id, 2026, $account->id, '20000000', 'Pendanaan tes');
        app(Budgets::class)->setBudget($department->id, 2026, '5000000');
    }

    private function actor(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function approved(string $amount = '4000000.50'): Proposal
    {
        $owner = $this->actor('karyawan');

        return Proposal::create(['user_id' => $owner->id, 'departemen_id' => $owner->departemen_id, 'judul' => 'Pencairan bertahap mini PC',
            'jenis' => 'barang', 'tanggal_pengajuan' => '2026-01-01', 'total_anggaran' => $amount, 'status' => 'disetujui_senior_pastor']);
    }

    private function payment(Proposal $proposal, string $amount): array
    {
        return ['operation_id' => (string) Str::uuid(), 'proposal_id' => $proposal->id, 'rekening_id' => Rekening::firstOrFail()->id,
            'jumlah_dana' => $amount, 'metode' => 'cash', 'catatan' => 'Tahap pembelian perangkat'];
    }

    public function test_payout_detail_automatically_uses_group_source_for_proposal_year(): void
    {
        $p = $this->approved();
        $future = Rekening::create(['nama_rekening' => 'Sumber tahun lain', 'saldo' => '5000000']);
        app(GroupFundingService::class)->save($p->departemen->grup_id, 2027, $future->id, '5000000', 'Pendanaan tahun berikutnya');
        Rekening::create(['nama_rekening' => 'Rekening grup lain', 'saldo' => '8000000']);
        $response = $this->actingAs($this->actor('keuangan'))->get(route('admin.pencairan.show', $p->id));
        $response->assertOk()->assertViewHas('year', 2026)->assertViewHas('sourceAccount', fn ($account) => $account->nama_rekening === 'Bank')
            ->assertViewHas('maximumCents', 400000050)->assertSee('Rekening sumber grup')->assertSee('data-payout-form', false)
            ->assertDontSee('Sumber tahun lain')->assertDontSee('Rekening grup lain')->assertDontSee('select name="rekening_id"', false);
        $this->get(route('admin.pencairan.index'))->assertOk()->assertDontSee('select name="rekening_id"', false);
        $this->actingAs($this->actor('admin'))->get(route('admin.pencairan.show', $p->id))->assertOk()->assertDontSee('data-payout-form', false);
        $bad = $this->payment($p, '100');
        $bad['rekening_id'] = $future->id;
        $this->actingAs($this->actor('keuangan'))->postJson(route('admin.pencairan.store'), $bad)->assertUnprocessable();
        $this->assertDatabaseCount('pencairan_dana', 0);
    }

    public function test_payout_three_installments_preserve_cents_history_and_ceiling(): void
    {
        $p = $this->approved();
        $account = Rekening::firstOrFail();
        $this->actingAs($this->actor('keuangan'));
        $first = $this->payment($p, '1000000');
        $this->postJson(route('admin.pencairan.store'), $first)->assertRedirect();
        $this->postJson(route('admin.pencairan.store'), $first)->assertRedirect();
        $this->assertDatabaseCount('pencairan_dana', 1);
        $this->assertSame('dicairkan_sebagian', $p->fresh()->status);
        $this->get(route('admin.pencairan.show', $p->id))->assertOk()->assertViewHas('remainingCents', 300000050)
            ->assertViewHas('maximumCents', 300000050)->assertSee('Tahap 1')->assertSee('3.000.000,50');
        $second = $this->payment($p, '1500000.25');
        $second['metode'] = 'transfer';
        $this->postJson(route('admin.pencairan.store'), $second)->assertUnprocessable();
        $second['bukti_transfer'] = UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf');
        $this->post(route('admin.pencairan.store'), $second)->assertRedirect();
        $this->assertSame('dicairkan_sebagian', $p->fresh()->status);
        $this->postJson(route('admin.pencairan.store'), $this->payment($p, '1500000.26'))->assertUnprocessable();
        $this->postJson(route('admin.pencairan.store'), $this->payment($p, '1500000.25'))->assertRedirect();
        $this->assertSame('dicairkan', $p->fresh()->status);
        $this->assertDatabaseCount('pencairan_dana', 3);
        $this->assertSame(400000050, Budgets::cents($p->pencairan()->sum('jumlah_dana')));
        $this->assertSame(1599999950, Budgets::cents($account->fresh()->saldo));
        $this->get(route('admin.pencairan.show', $p->id))->assertOk()->assertViewHas('remainingCents', 0)
            ->assertSee('Tahap 3')->assertSee('Lihat bukti transfer')->assertDontSee('data-payout-form', false);
        $this->get(route('admin.pencairan.index'))->assertOk()->assertViewHas('proposals', fn ($rows) => $rows->doesntContain('id', $p->id));
        $this->postJson(route('admin.pencairan.store'), $this->payment($p, '0.01'))->assertUnprocessable();
        $this->assertSame(1599999950, Budgets::cents($account->fresh()->saldo));
    }

    public function test_payout_validation_errors_are_visible_and_unfunded_or_closed_forms_are_blocked(): void
    {
        $p = $this->approved();
        $url = route('admin.pencairan.show', $p->id);
        $data = $this->payment($p, '1000');
        unset($data['catatan']);
        $this->actingAs($this->actor('keuangan'))->from($url)->post(route('admin.pencairan.store'), $data)->assertRedirect($url)->assertSessionHasErrors('catatan');
        $this->get($url)->assertOk()->assertSee('Pencairan belum tersimpan.');
        BudgetPeriod::whereKey(2026)->update(['status' => 'closed']);
        $this->get($url)->assertOk()->assertSee('Periode budget 2026 ditutup')->assertDontSee('data-payout-form', false);
        BudgetPeriod::whereKey(2026)->update(['status' => 'open']);
        GroupFunding::firstOrFail()->delete();
        $this->get($url)->assertOk()->assertSee('Rekening sumber grup tahun 2026 belum ditetapkan')->assertDontSee('data-payout-form', false);
    }

    public function test_refund_between_installments_does_not_increase_gross_payout_limit(): void
    {
        $p = $this->approved('2000000');
        $account = Rekening::firstOrFail();
        $this->actingAs($this->actor('keuangan'))->postJson(route('admin.pencairan.store'), $this->payment($p, '1000000'))->assertRedirect();
        $this->actingAs($this->actor('karyawan'))->postJson(route('admin.pengembalian_dana.store'), [
            'operation_id' => (string) Str::uuid(), 'proposal_id' => $p->id, 'jumlah' => '200000', 'metode' => 'cash',
        ])->assertRedirect();
        $refund = PengembalianDana::firstOrFail();
        $this->actingAs($this->actor('keuangan'))->postJson(route('admin.pengembalian_dana.approve', $refund->id), ['rekening_id' => $account->id])->assertRedirect();
        $this->get(route('admin.pencairan.show', $p->id))->assertOk()->assertViewHas('remainingCents', 100000000)->assertViewHas('maximumCents', 100000000);
        $this->postJson(route('admin.pencairan.store'), $this->payment($p, '1200000'))->assertUnprocessable();
        $this->postJson(route('admin.pencairan.store'), $this->payment($p, '1000000'))->assertRedirect();
        $this->assertSame(200000000, Budgets::cents($p->pencairan()->sum('jumlah_dana')));
        $this->assertSame(1820000000, Budgets::cents($account->fresh()->saldo));
    }

    public function test_payout_pdf_renders_all_installments_and_is_restricted_to_finance_and_admin(): void
    {
        $p = $this->approved('1000000.50');
        $this->actingAs($this->actor('keuangan'));
        $url = route('admin.pencairan.pdf', $p->id);
        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->postJson(route('admin.pencairan.store'), $this->payment($p, '250000.25'))->assertRedirect();
        $this->postJson(route('admin.pencairan.store'), $this->payment($p, '150000.25'))->assertRedirect();
        $response = $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('pencairan-proposal-'.$p->id.'.pdf', $response->headers->get('content-disposition'));
        $page = $this->get(route('admin.pencairan.show', $p->id))->assertOk()->assertSee($url, false);
        $data = $page->original->getData();
        $data['balance'] = app(\App\Services\FinancialBalance::class)->values($p, false);
        $data['printedBy'] = 'Keuangan';
        $data['printedAt'] = '30/10/2026 15:00 WIB';
        $html = view('backend.pencairan.report_pdf', $data)->render();
        foreach (['karyawan', 'IT / Office', 'Bank', 'Tahap 1', 'Tahap 2', '250.000,25', '150.000,25', '400.000,50', '600.000,00',
            'Tahap pembelian perangkat', 'Total nota penggunaan', 'Pengembalian diterima keuangan'] as $detail) {
            $this->assertStringContainsString($detail, $html);
        }
        $this->actingAs($this->actor('admin'))->get($url)->assertOk();
        $this->actingAs($this->actor('karyawan'))->getJson($url)->assertForbidden();
        auth()->logout();
        $this->getJson($url)->assertUnauthorized();
    }
}
