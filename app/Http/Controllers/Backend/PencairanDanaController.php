<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\BudgetPeriod;
use App\Models\GroupFunding;
use App\Models\PencairanDana;
use App\Models\Proposal;
use App\Models\Rekening;
use App\Services\DepartmentBudgetService;
use App\Services\FinancialOperation;
use App\Services\FinancialBalance;
use App\Services\GroupFundingService;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PencairanDanaController extends Controller
{
    public function index()
    {
        $proposals = Proposal::with('departemen', 'user', 'pencairan')
            ->whereIn('status', ['disetujui_senior_pastor', 'dicairkan_sebagian'])
            ->get()
            ->filter(function ($p) {
                $totalCair = $p->pencairan->sum('jumlah_dana');

                return $totalCair < $p->total_anggaran;
            });

        $pencairans = PencairanDana::with(['proposal.nota_penggunaan', 'rekening'])
            ->latest()
            ->get();

        return view('backend.pencairan.index', compact('proposals', 'pencairans'));
    }

    public function show($id)
    {
        return view('backend.pencairan.show', $this->detail($id));
    }

    public function exportPdf($id)
    {
        $data = $this->detail($id);
        $data['balance'] = app(FinancialBalance::class)->values($data['proposal'], false);
        $data['printedBy'] = auth()->user()->name;
        $data['printedAt'] = now()->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB';

        return Pdf::loadView('backend.pencairan.report_pdf', $data)->setPaper('a4')->download('pencairan-proposal-'.$data['proposal']->id.'.pdf');
    }

    private function detail($id): array
    {
        $proposal = Proposal::with([
            'user',
            'departemen.grup',
            'items',
            'approval_flows.approver',
            'nota_penggunaan',
            'pencairan.rekening',
        ])->findOrFail($id);

        $year = (int) substr($proposal->tanggal_pengajuan, 0, 4);
        $funding = GroupFunding::with('rekening')->where('grup_id', $proposal->departemen->grup_id)->where('tahun', $year)->first();
        $sourceAccount = $funding?->rekening;
        $paidCents = DepartmentBudgetService::cents($proposal->pencairan->sum('jumlah_dana'));
        $remainingCents = max(0, DepartmentBudgetService::cents($proposal->total_anggaran) - $paidCents);
        $spendableCents = app(DepartmentBudgetService::class)->spendable($proposal->departemen_id, $year, $proposal->id);
        $maximumCents = max(0, min($remainingCents, $spendableCents, DepartmentBudgetService::cents($sourceAccount?->saldo ?? 0)));
        $blockReason = null;
        if (! $sourceAccount) {
            $blockReason = 'Rekening sumber grup tahun '.$year.' belum ditetapkan. Lengkapi pendanaan di menu Budget Departemen.';
        } elseif (BudgetPeriod::whereKey($year)->value('status') === 'closed') {
            $blockReason = 'Periode budget '.$year.' ditutup. Buka kembali periode sebelum mencairkan dana.';
        } else {
            try {
                app(GroupFundingService::class)->assertBacked($sourceAccount->id);
            } catch (ValidationException $e) {
                $blockReason = 'Pendanaan grup belum didukung saldo rekening yang cukup. Periksa pendanaan di menu Budget Departemen.';
            }
        }
        if (! $blockReason && $remainingCents > 0 && $maximumCents === 0) {
            $blockReason = 'Saldo rekening atau budget yang dapat digunakan belum mencukupi untuk pencairan.';
        }
        $canDisburse = auth()->user()->role === 'keuangan' && ! $blockReason && $maximumCents > 0
            && in_array($proposal->status, ['disetujui_senior_pastor', 'dicairkan_sebagian']);

        return compact('proposal', 'year', 'sourceAccount', 'paidCents', 'remainingCents', 'spendableCents', 'maximumCents', 'blockReason', 'canDisburse');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'keuangan' && $request->user()->is_active, 403);
        $data = $request->validate(['operation_id' => 'required|uuid', 'proposal_id' => 'required|integer|exists:proposal,id', 'rekening_id' => 'required|integer|exists:rekening,id',
            'jumlah_dana' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999999.99', 'metode' => 'required|in:transfer,cash',
            'catatan' => 'required|string|max:500', 'bukti_transfer' => 'required_if:metode,transfer|nullable|file|mimes:pdf,jpg,jpeg,png|max:2048']);
        $stub = Proposal::findOrFail($data['proposal_id']);
        $path = null;
        try {
            DB::transaction(function () use ($request, $data, $stub, &$path) {
                $service = app(DepartmentBudgetService::class);
                $year = (int) substr($stub->tanggal_pengajuan, 0, 4);
                $service->ensureOpen($year);
                Departemen::whereKey($stub->departemen_id)->lockForUpdate()->firstOrFail();
                if (app(FinancialOperation::class)->replay($data['operation_id'], $request->user()->id, 'payout', $data)) {
                    return;
                }
                $proposal = Proposal::lockForUpdate()->findOrFail($stub->id);
                $account = Rekening::lockForUpdate()->findOrFail($data['rekening_id']);
                $funding = app(GroupFundingService::class)->requireDepartment($proposal->departemen_id, $year);
                if ((int) $funding->rekening_id !== (int) $account->id) {
                    throw ValidationException::withMessages(['rekening_id' => 'Pencairan wajib memakai rekening sumber pendanaan grup tahun proposal.']);
                }
                if (! in_array($proposal->status, ['disetujui_senior_pastor', 'dicairkan_sebagian'])) {
                    throw ValidationException::withMessages(['proposal' => 'Proposal belum disetujui untuk pencairan.']);
                }
                $amount = DepartmentBudgetService::cents($data['jumlah_dana']);
                $paid = DepartmentBudgetService::cents($proposal->pencairan()->sum('jumlah_dana'));
                if ($paid + $amount > DepartmentBudgetService::cents($proposal->total_anggaran)) {
                    throw ValidationException::withMessages(['jumlah_dana' => 'Pencairan melebihi sisa plafon proposal.']);
                }
                if ($amount > $service->spendable($proposal->departemen_id, $year, $proposal->id)) {
                    throw ValidationException::withMessages(['jumlah_dana' => 'Budget tidak cukup setelah memperhitungkan proposal lain yang dicadangkan.']);
                }
                if ($amount > DepartmentBudgetService::cents($account->saldo)) {
                    throw ValidationException::withMessages(['jumlah_dana' => 'Saldo rekening tidak mencukupi.']);
                }
                $path = $request->file('bukti_transfer')?->store('bukti_transfer', 'local');
                $account->decrement('saldo', $data['jumlah_dana']);
                $payout = PencairanDana::create(collect($data)->except(['bukti_transfer', 'operation_id'])->all() + ['bukti_transfer' => $path]);
                $proposal->update(['status' => $paid + $amount === DepartmentBudgetService::cents($proposal->total_anggaran) ? 'dicairkan' : 'dicairkan_sebagian']);
                app(NotificationService::class)->queue($proposal->user->nohp, 'Pencairan dana Rp '.number_format($data['jumlah_dana'], 2, ',', '.').' untuk proposal '.$proposal->judul.' telah dicatat.', 'Pencairan dana', $proposal->user_id, 'payout-'.$payout->id);
                app(FinancialOperation::class)->complete($data['operation_id']);
            }, 3);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return back()->with('success', 'Pencairan dicatat. Status pengiriman pesan dapat diperiksa pada menu Notifikasi.');
    }
}
