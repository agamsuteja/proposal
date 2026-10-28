<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApprovalProposal;
use App\Models\Departemen;
use App\Models\NotaPenggunaanProposal;
use App\Models\Proposal;
use App\Services\DepartmentBudgetService as Budgets;
use App\Services\FinancialBalance;
use App\Services\FinancialOperation;
use App\Services\GroupFundingService;
use App\Services\NotificationService;
use App\Services\ProposalApprovers;
use App\Services\WaTemplate;
use App\Support\Access;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProposalController extends Controller
{
    public function index()
    {
        $query = Access::proposals(auth()->user())->with(['departemen', 'user', 'items', 'pencairan', 'approval_flows'])->latest();
        if (in_array(request('jenis'), ['barang', 'acara', 'maintenance'])) {
            $query->where('jenis', request('jenis'));
        }

        return view('backend.proposal.index', ['proposals' => $query->paginate(10)->withQueryString(), 'user' => auth()->user()]);
    }

    public function show($id)
    {
        $proposal = Proposal::with(['user', 'departemen', 'items', 'pencairan.rekening', 'approval_flows.approver', 'nota_penggunaan', 'pengembalianDana'])->findOrFail($id);
        Access::viewProposal(auth()->user(), $proposal);
        $b = app(FinancialBalance::class)->values($proposal, false);

        return view('backend.proposal.show', ['proposal' => $proposal, 'totalCair' => $b['paid'] / 100, 'totalDigunakan' => $b['receipts'] / 100, 'totalRefund' => $b['refunds'] / 100, 'sisaDana' => $b['remaining'] / 100]);
    }

    public function create()
    {
        $options = Access::departments(auth()->user())->orderBy('nama_departemen')->get();
        if (in_array(auth()->user()->role, ['karyawan', 'departemen_head'])) {
            abort_if($options->isEmpty(), 422, 'Akun belum ditempatkan pada departemen. Hubungi admin.');

            return view('backend.proposal.create', ['departemen' => $options->first()]);
        }

        return view('backend.proposal.create', ['departemens' => $options]);
    }

    private function data(Request $request, ?Proposal $proposal = null): array
    {
        $jenis = $proposal?->jenis ?? $request->input('jenis');
        if ($request->filled('total_anggaran_manual')) {
            $request->merge(['total_anggaran_manual' => Money::normalize($request->input('total_anggaran_manual'))]);
        }
        if (is_array($request->input('items'))) {
            $request->merge(['items' => array_map(function ($i) {
                if (! is_array($i)) {
                    return $i;
                }
                if (isset($i['anggaran']) && $i['anggaran'] !== '') {
                    $i['anggaran'] = Money::normalize($i['anggaran']);
                }

                return $i;
            }, $request->input('items'))]);
        }
        $rules = ['judul' => 'required|string|max:150', 'tujuan' => 'nullable|string|max:10000', 'tanggal_pengajuan' => 'required|date',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048', 'judul_acara' => 'nullable|string|max:150', 'tanggal_acara' => 'nullable|date', 'lokasi_acara' => 'nullable|string|max:255', 'keterangan' => 'nullable|string|max:10000'];
        if (! $proposal) {
            $rules['jenis'] = 'required|in:barang,maintenance,acara';
        }
        if (in_array($jenis, ['barang', 'maintenance'])) {
            $rules += ['items' => 'required|array|min:1|max:100',
                'items.*.nama_barang' => 'required|string|max:255', 'items.*.anggaran' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999999.99',
                'items.*.merk_tipe' => 'nullable|string|max:255', 'items.*.jumlah' => 'nullable|integer|min:1|max:1000000', 'items.*.tujuan_pembelian' => 'nullable|string|max:5000'];
        } else {
            $rules['total_anggaran_manual'] = 'required|numeric|decimal:0,2|min:0.01|max:9999999999999.99';
        }

        $data = $request->validate($rules);
        if ($jenis === 'acara') {
            $data['judul_acara'] = $data['judul_acara'] ?? $proposal?->judul_acara ?? $data['judul'];
        } else {
            unset($data['judul_acara']);
        }

        return $data;
    }

    private function total(array $data, string $jenis): string
    {
        $cents = in_array($jenis, ['barang', 'maintenance']) ? array_sum(array_map(fn ($i) => Budgets::cents($i['anggaran']), $data['items'])) : Budgets::cents($data['total_anggaran_manual']);
        if ($cents > 999999999999999) {
            throw ValidationException::withMessages(['jumlah' => 'Total nominal terlalu besar.']);
        }

        return Budgets::amount($cents);
    }

    private function items(Proposal $proposal, array $data): void
    {
        // Individual deletes fire audit events; database bulk deletes do not.
        foreach ($proposal->items()->get() as $item) {
            $item->delete();
        }
        foreach ($data['items'] ?? [] as $item) {
            $proposal->items()->create($item);
        }
    }

    private function flows(Proposal $proposal, array $approvers, bool $revised = false): void
    {
        foreach ($proposal->approval_flows()->get() as $flow) {
            $flow->delete();
        }
        $order = 1;
        $firstFlowId = null;
        foreach ($approvers as $stage => $user) {
            $flow = ApprovalProposal::create(['proposal_id' => $proposal->id, 'approver_id' => $user->id, 'tahap' => $stage, 'urutan' => $order++, 'status' => 'pending']);
            $firstFlowId ??= $flow->id;
        }
        $first = reset($approvers);
        $message = $revised
            ? WaTemplate::diajukanKembali($first->name, $proposal->departemen->nama_departemen, $proposal->judul, $proposal->total_anggaran)
            : WaTemplate::proposalBaru($first->name, $proposal->departemen->nama_departemen, $proposal->judul, $proposal->total_anggaran);
        app(NotificationService::class)->queue($first->nohp, $message, $revised ? 'Proposal revisi diajukan kembali' : 'Proposal baru menunggu persetujuan', $first->id, 'proposal-submitted-'.$proposal->id.'-'.$firstFlowId);
    }

    private function checkSubmissionBudget(int $department, int $year, string $total): void
    {
        app(GroupFundingService::class)->requireDepartment($department, $year);
        $available = app(Budgets::class)->available($department, $year);
        $requested = Budgets::cents($total);
        if ($requested > $available) {
            $rupiah = fn ($cents) => 'Rp '.number_format($cents / 100, 2, ',', '.');
            throw ValidationException::withMessages(['budget' => 'Proposal belum dapat diajukan. Total proposal '.$rupiah($requested).', budget departemen tahun '.$year.' tersedia '.$rupiah($available).'. Kekurangan budget '.$rupiah($requested - $available).'. Ajukan tambahan budget dari departemen lain dalam grup yang sama dan tunggu approval serta transfer keuangan selesai, lalu ajukan proposal kembali.']);
        }
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $departmentId = in_array($request->user()->role, ['karyawan', 'departemen_head']) ? $request->user()->departemen_id : $request->input('departemen_id');
        $department = Access::departments($request->user())->findOrFail($departmentId);
        $approvers = app(ProposalApprovers::class)->forDepartment($department);
        $path = null;
        try {
            DB::transaction(function () use ($request, $data, $department, $approvers, &$path) {
                app(Budgets::class)->ensureOpen((int) substr($data['tanggal_pengajuan'], 0, 4));
                Departemen::whereKey($department->id)->lockForUpdate()->firstOrFail();
                $total = $this->total($data, $data['jenis']);
                $this->checkSubmissionBudget($department->id, (int) substr($data['tanggal_pengajuan'], 0, 4), $total);
                $path = $request->file('lampiran')?->store('proposal_lampiran', 'local');
                $proposal = Proposal::create(collect($data)->except(['items', 'lampiran', 'total_anggaran_manual'])->all() + [
                    'user_id' => $request->user()->id, 'departemen_id' => $department->id, 'total_anggaran' => $total, 'lampiran' => $path, 'status' => 'diajukan']);
                $this->items($proposal, $data);
                $this->flows($proposal, $approvers);
            }, 3);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return redirect()->route('admin.proposal.index')->with('success', 'Proposal diajukan. Status notifikasi dapat dipantau oleh admin/keuangan.');
    }

    public function edit($id)
    {
        $proposal = Proposal::with('items')->findOrFail($id);
        Access::editProposal(auth()->user(), $proposal);

        return view('backend.proposal.edit', ['proposal' => $proposal, 'departemen' => Access::departments(auth()->user())->get()]);
    }

    public function update(Request $request, $id)
    {
        $stub = Proposal::findOrFail($id);
        Access::editProposal($request->user(), $stub);
        $data = $this->data($request, $stub);
        $path = null;
        try {
            DB::transaction(function () use ($request, $stub, $data, &$path) {
                app(Budgets::class)->ensureOpen((int) substr($data['tanggal_pengajuan'], 0, 4));
                app(Budgets::class)->ensureOpen((int) substr($stub->tanggal_pengajuan, 0, 4));
                Departemen::whereKey($stub->departemen_id)->lockForUpdate()->firstOrFail();
                $proposal = Proposal::lockForUpdate()->findOrFail($stub->id);
                Access::editProposal($request->user(), $proposal);
                $total = $this->total($data, $proposal->jenis);
                $this->checkSubmissionBudget($proposal->departemen_id, (int) substr($data['tanggal_pengajuan'], 0, 4), $total);
                $approvers = app(ProposalApprovers::class)->forDepartment($proposal->departemen);
                $revised = $proposal->status === 'revisi';
                $oldPath = $proposal->lampiran;
                $path = $request->file('lampiran')?->store('proposal_lampiran', 'local');
                $proposal->update(collect($data)->except(['items', 'lampiran', 'total_anggaran_manual'])->all() + [
                    'total_anggaran' => $total, 'lampiran' => $path ?? $oldPath, 'status' => 'diajukan']);
                $this->items($proposal, $data);
                $this->flows($proposal, $approvers, $revised);
                if ($path && $oldPath) {
                    DB::afterCommit(fn () => Storage::disk(Storage::disk('local')->exists($oldPath) ? 'local' : 'public')->delete($oldPath));
                }
            }, 3);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return redirect()->route('admin.proposal.index')->with('success', 'Proposal diperbarui dan seluruh tahapan approval diulang.');
    }

    public function destroy($id)
    {
        $stub = Proposal::findOrFail($id);
        Access::editProposal(auth()->user(), $stub);
        DB::transaction(function () use ($stub) {
            app(Budgets::class)->ensureOpen((int) substr($stub->tanggal_pengajuan, 0, 4));
            Departemen::whereKey($stub->departemen_id)->lockForUpdate()->firstOrFail();
            $proposal = Proposal::lockForUpdate()->findOrFail($stub->id);
            Access::editProposal(auth()->user(), $proposal);
            foreach ($proposal->items()->get() as $item) {
                $item->delete();
            }
            foreach ($proposal->approval_flows()->get() as $flow) {
                $flow->delete();
            }
            $path = $proposal->lampiran;
            $proposal->delete();
            if ($path) {
                DB::afterCommit(fn () => Storage::disk(Storage::disk('local')->exists($path) ? 'local' : 'public')->delete($path));
            }
        }, 3);

        return back()->with('success', 'Proposal dihapus dan riwayat perubahan dicatat.');
    }

    public function uploadNota(Request $request, $id)
    {
        $stub = Proposal::findOrFail($id);
        Access::viewProposal($request->user(), $stub);
        abort_unless((int) $stub->user_id === (int) $request->user()->id, 403);
        $data = $request->validate(['operation_id' => 'required|uuid', 'nota' => 'required|array|min:1|max:50', 'nota.*.file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'nota.*.jumlah' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999999.99', 'nota.*.keterangan' => 'nullable|string|max:255']);
        $paths = [];
        try {
            DB::transaction(function () use ($stub, $data, $request, &$paths) {
                app(Budgets::class)->ensureOpen((int) substr($stub->tanggal_pengajuan, 0, 4));
                Departemen::whereKey($stub->departemen_id)->lockForUpdate()->firstOrFail();
                if (app(FinancialOperation::class)->replay($data['operation_id'], $request->user()->id, 'receipt-'.$stub->id, $data)) {
                    return;
                }
                $proposal = Proposal::lockForUpdate()->findOrFail($stub->id);
                abort_unless(in_array($proposal->status, ['dicairkan', 'dicairkan_sebagian']), 409, 'Proposal belum dicairkan.');
                $total = array_sum(array_map(fn ($n) => Budgets::cents($n['jumlah']), $data['nota']));
                if ($total > app(FinancialBalance::class)->values($proposal)['remaining']) {
                    throw ValidationException::withMessages(['nota' => 'Total nota melebihi sisa dana setelah nota dan pengembalian sebelumnya.']);
                }
                foreach ($data['nota'] as $note) {
                    $path = $note['file']->store('nota_penggunaan', 'local');
                    $paths[] = $path;
                    NotaPenggunaanProposal::create(['proposal_id' => $proposal->id, 'file_nota' => $path, 'jumlah' => $note['jumlah'], 'keterangan' => $note['keterangan'] ?? null]);
                }
                app(FinancialOperation::class)->complete($data['operation_id']);
            }, 3);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($paths);
            throw $e;
        }

        return back()->with('success', 'Nota berhasil disimpan.');
    }

    public function requestRevision(Request $request, int $id)
    {
        $data = $request->validate(['alasan' => 'required|string|min:5|max:1000']);
        $stub = Proposal::findOrFail($id);
        DB::transaction(function () use ($stub, $data) {
            app(Budgets::class)->ensureOpen((int) substr($stub->tanggal_pengajuan, 0, 4));
            Departemen::whereKey($stub->departemen_id)->lockForUpdate()->firstOrFail();
            $proposal = Proposal::lockForUpdate()->findOrFail($stub->id);
            abort_if($proposal->pencairan()->exists(), 409, 'Proposal sudah dicairkan dan tidak dapat dikembalikan untuk revisi.');
            $proposal->update(['status' => 'revisi']);
            ActivityLog::create(['user_id' => auth()->id(), 'action' => 'proposal.revision_requested', 'subject_type' => 'proposal', 'subject_id' => (string) $proposal->id, 'after' => ['alasan' => $data['alasan']]]);
            app(NotificationService::class)->queue($proposal->user->nohp, WaTemplate::revisiKePemohon($proposal->user->name, $proposal->judul, $data['alasan']), 'Permintaan revisi admin', $proposal->user_id);
        }, 3);

        return back()->with('success', 'Proposal dikembalikan ke pemohon untuk revisi dan approval ulang.');
    }
}
