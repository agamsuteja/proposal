<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Grup;
use App\Models\PengembalianDana;
use App\Models\Proposal;
use App\Models\Rekening;
use App\Models\User;
use App\Services\DepartmentBudgetService as Budgets;
use App\Services\FinancialBalance;
use App\Services\FinancialOperation;
use App\Services\NotificationService;
use App\Support\Access;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PengembalianDanaController extends Controller
{
    private function query(Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,diterima,ditolak', 'grup_id' => 'nullable|integer', 'tanggal_awal' => 'nullable|date', 'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal_awal']);
        $proposals = Access::proposals($request->user());
        if ($request->filled('grup_id')) {
            $proposals->whereHas('departemen', fn ($d) => $d->where('grup_id', $request->grup_id));
        }

        return PengembalianDana::with(['proposal.user.grup', 'proposal.departemen', 'rekening'])->whereIn('proposal_id', $proposals->select('id'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('tanggal_awal'), fn ($q) => $q->whereDate('created_at', '>=', $request->tanggal_awal))
            ->when($request->filled('tanggal_akhir'), fn ($q) => $q->whereDate('created_at', '<=', $request->tanggal_akhir));
    }

    public function index(Request $request)
    {
        $q = $this->query($request);

        return view('backend.pengembalian_dana.index', ['data' => (clone $q)->latest()->paginate(10)->withQueryString(),
            'totalDiterima' => (clone $q)->where('status', 'diterima')->sum('jumlah'),
            'grups' => Grup::whereIn('id', Access::departments($request->user())->select('grup_id'))->get()]);
    }

    public function create($proposal)
    {
        $p = Proposal::findOrFail($proposal);
        Access::viewProposal(auth()->user(), $p);
        abort_unless((int) $p->user_id === (int) auth()->id(), 403);

        return redirect()->route('admin.proposal.show', $p->id)->with('info', 'Gunakan formulir Pengembalian Dana pada detail proposal.');
    }

    public function show($id)
    {
        $data = PengembalianDana::with(['proposal.user', 'rekening'])->findOrFail($id);
        Access::viewProposal(auth()->user(), $data->proposal);

        return view('backend.pengembalian_dana.show', ['data' => $data, 'rekening' => auth()->user()->role === 'keuangan' ? Rekening::orderBy('nama_rekening')->get() : collect()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['operation_id' => 'required|uuid', 'proposal_id' => 'required|integer|exists:proposal,id', 'jumlah' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999999.99',
            'metode' => 'required|in:cash,transfer', 'bukti' => 'required_if:metode,transfer|nullable|file|mimes:pdf,jpg,jpeg,png|max:2048', 'catatan' => 'nullable|string|max:1000']);
        $stub = Proposal::findOrFail($data['proposal_id']);
        Access::viewProposal($request->user(), $stub);
        abort_unless((int) $stub->user_id === (int) $request->user()->id, 403);
        $path = null;
        try {
            DB::transaction(function () use ($request, $data, $stub, &$path) {
                app(Budgets::class)->ensureOpen((int) substr($stub->tanggal_pengajuan, 0, 4));
                Departemen::whereKey($stub->departemen_id)->lockForUpdate()->firstOrFail();
                if (app(FinancialOperation::class)->replay($data['operation_id'], $request->user()->id, 'refund', $data)) {
                    return;
                }
                $proposal = Proposal::lockForUpdate()->findOrFail($stub->id);
                if (Budgets::cents($data['jumlah']) > app(FinancialBalance::class)->values($proposal)['remaining']) {
                    throw ValidationException::withMessages(['jumlah' => 'Pengembalian melebihi sisa dana setelah nota dan pengembalian sebelumnya.']);
                }
                $path = $request->file('bukti')?->store('bukti_pengembalian', 'local');
                $refund = PengembalianDana::create(collect($data)->except(['bukti', 'operation_id'])->all() + ['bukti' => $path, 'status' => 'pending', 'tanggal_pengembalian' => now()]);
                foreach (User::where('role', 'keuangan')->where('is_active', 1)->get() as $finance) {
                    app(NotificationService::class)->queue($finance->nohp, 'Pengembalian dana #'.$refund->id.' menunggu verifikasi untuk proposal '.$proposal->judul, 'Verifikasi pengembalian', $finance->id, 'refund-'.$refund->id.'-'.$finance->id);
                }
                app(FinancialOperation::class)->complete($data['operation_id']);
            }, 3);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return back()->with('success', 'Pengembalian diajukan dan menunggu verifikasi keuangan.');
    }

    public function approve(Request $request, $id)
    {
        $data = $request->validate(['rekening_id' => 'required|integer|exists:rekening,id']);
        $stub = PengembalianDana::with('proposal')->findOrFail($id);
        DB::transaction(function () use ($data, $stub) {
            app(Budgets::class)->ensureOpen((int) substr($stub->proposal->tanggal_pengajuan, 0, 4));
            Departemen::whereKey($stub->proposal->departemen_id)->lockForUpdate()->firstOrFail();
            $proposal = Proposal::lockForUpdate()->findOrFail($stub->proposal_id);
            $refund = PengembalianDana::lockForUpdate()->findOrFail($stub->id);
            abort_unless($refund->status === 'pending', 409, 'Data sudah diproses.');
            $b = app(FinancialBalance::class)->values($proposal);
            if ($b['remaining'] < 0) {
                throw ValidationException::withMessages(['jumlah' => 'Total nota dan pengembalian melampaui pencairan. Rekonsiliasi data terlebih dahulu.']);
            }
            $account = Rekening::lockForUpdate()->findOrFail($data['rekening_id']);
            $account->increment('saldo', $refund->jumlah);
            $refund->update(['status' => 'diterima', 'rekening_id' => $account->id]);
            app(NotificationService::class)->queue($proposal->user->nohp, 'Pengembalian dana #'.$refund->id.' telah diterima keuangan.', 'Pengembalian diterima', $proposal->user_id, 'refund-accepted-'.$refund->id);
        }, 3);

        return back()->with('success', 'Pengembalian diterima dan saldo diperbarui.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['catatan' => 'nullable|string|max:1000']);
        $stub = PengembalianDana::with('proposal')->findOrFail($id);
        DB::transaction(function () use ($stub, $request) {
            app(Budgets::class)->ensureOpen((int) substr($stub->proposal->tanggal_pengajuan, 0, 4));
            Departemen::whereKey($stub->proposal->departemen_id)->lockForUpdate()->firstOrFail();
            Proposal::whereKey($stub->proposal_id)->lockForUpdate()->firstOrFail();
            $refund = PengembalianDana::lockForUpdate()->findOrFail($stub->id);
            abort_unless($refund->status === 'pending', 409);
            $refund->update(['status' => 'ditolak', 'catatan' => $request->input('catatan', $refund->catatan)]);
        }, 3);

        return back()->with('success', 'Pengembalian ditolak; saldo rekening tidak berubah.');
    }

    public function exportPdf(Request $request)
    {
        $data = $this->query($request)->latest()->get();

        return Pdf::loadView('backend.pengembalian_dana.laporan_pdf',['data' => $data, 'totalDiterima' => $data->where('status','diterima')->sum('jumlah'), 'request' => $request])->download('laporan_pengembalian_dana.pdf');
    }
}
