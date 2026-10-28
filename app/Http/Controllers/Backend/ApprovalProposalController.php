<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ApprovalProposal;
use App\Models\Departemen;
use App\Models\Proposal;
use App\Models\User;
use App\Services\DepartmentBudgetService as Budgets;
use App\Services\GroupFundingService;
use App\Services\NotificationService;
use App\Services\WaTemplate;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalProposalController extends Controller
{
    public function index()
    {
        $q = ApprovalProposal::with(['proposal.user', 'proposal.departemen', 'proposal.items', 'proposal.approval_flows'])
            ->where('approver_id', auth()->id())->whereHas('proposal', fn ($p) => $p->whereIn('departemen_id', Access::departments(auth()->user())->select('id')));
        $pending = (clone $q)->where('status', 'pending')->get()->filter(fn ($f) => $f->proposal && in_array($f->proposal->status, ['diajukan', 'disetujui_mh', 'disetujui_gh']))->map(function ($f) {
            $f->can_approve = $f->tahap === auth()->user()->role && ! $f->proposal->approval_flows->where('urutan', '<', $f->urutan)->contains(fn ($p) => $p->status !== 'disetujui');
            $f->reason = $f->can_approve ? null : 'Menunggu persetujuan tahap sebelumnya.';

            return $f;
        });

        return view('backend.approval.index', ['pendingApprovals' => $pending, 'historyApprovals' => (clone $q)->whereIn('status', ['disetujui', 'ditolak'])->latest('updated_at')->paginate(10)]);
    }

    public function show($id)
    {
        $proposal = Proposal::with(['user', 'departemen', 'items', 'approval_flows.approver'])->findOrFail($id);
        Access::viewProposal(auth()->user(), $proposal);
        $flow = $proposal->approval_flows->firstWhere('approver_id', auth()->id());
        abort_unless($flow || in_array(auth()->user()->role, ['admin', 'keuangan']), 403);
        $can = $flow && $flow->status === 'pending' && $flow->tahap === auth()->user()->role && $proposal->status !== 'revisi'
            && ! $proposal->approval_flows->where('urutan', '<', $flow->urutan)->contains(fn ($p) => $p->status !== 'disetujui');

        return view('backend.approval.show', ['proposal' => $proposal, 'currentFlow' => $flow, 'canApprove' => $can]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate(['status' => 'required|in:disetujui,ditolak', 'catatan' => 'nullable|string|max:1000']);
        $stub = ApprovalProposal::with('proposal')->findOrFail($id);
        abort_unless((int) $stub->approver_id === (int) $request->user()->id, 403);
        Access::viewProposal($request->user(), $stub->proposal);
        DB::transaction(function () use ($request, $stub, $data) {
            $budgets = app(Budgets::class);
            $year = (int) substr($stub->proposal->tanggal_pengajuan, 0, 4);
            $budgets->ensureOpen($year);
            Departemen::whereKey($stub->proposal->departemen_id)->lockForUpdate()->firstOrFail();
            $proposal = Proposal::lockForUpdate()->findOrFail($stub->proposal_id);
            $flow = ApprovalProposal::lockForUpdate()->findOrFail($stub->id);
            abort_unless((int) $flow->approver_id === (int) $request->user()->id && $flow->tahap === $request->user()->role, 403);
            Access::viewProposal($request->user(), $proposal);
            abort_unless(in_array($proposal->status, ['diajukan', 'disetujui_mh', 'disetujui_gh']) && $flow->status === 'pending', 409, 'Persetujuan sudah diproses atau proposal perlu revisi.');
            if ($proposal->approval_flows()->where('urutan', '<', $flow->urutan)->where('status', '!=', 'disetujui')->exists()) {
                throw ValidationException::withMessages(['approval' => 'Tahap sebelumnya belum disetujui.']);
            }
            $flows = $proposal->approval_flows()->orderBy('urutan')->get();
            if ($data['status'] === 'disetujui' && $flows->pluck('tahap')->all() !== ['departemen_head', 'group_head', 'gembala']) {
                throw ValidationException::withMessages(['approval' => 'Alur lama tidak lengkap. Pemohon harus merevisi dan mengajukan ulang.']);
            }
            if ($data['status'] === 'disetujui' && $flow->tahap === 'gembala') {
                app(GroupFundingService::class)->requireDepartment($proposal->departemen_id, $year);
            }
            if ($data['status'] === 'disetujui' && $flow->tahap === 'gembala' && Budgets::cents($proposal->total_anggaran) > $budgets->available($proposal->departemen_id, $year)) {
                throw ValidationException::withMessages(['budget' => 'Budget belum cukup untuk mencadangkan proposal. Ajukan tambahan budget sebelum persetujuan akhir.']);
            }
            $flow->update(['status' => $data['status'], 'catatan' => $data['catatan'] ?? null, 'tanggal_approve' => now()]);
            $proposal->update(['status' => $data['status'] === 'ditolak' ? 'revisi' : match ($flow->tahap) {
                'departemen_head' => 'disetujui_mh','group_head' => 'disetujui_gh',default => 'disetujui_senior_pastor'
            }]);
            if ($data['status'] === 'ditolak') {
                app(NotificationService::class)->queue($proposal->user->nohp, WaTemplate::revisiKePemohon($proposal->user->name, $proposal->judul, $data['catatan'] ?? null), 'Proposal memerlukan revisi', $proposal->user_id, 'proposal-reject-'.$flow->id.'-'.$flow->updated_at->timestamp);
            } else {
                $next = $proposal->approval_flows()->where('status', 'pending')->orderBy('urutan')->first();
                if ($next) {
                    $message = match ($next->tahap) {
                        'group_head' => WaTemplate::proposalMasukGroupHead($next->approver->name, $proposal->departemen->nama_departemen, $proposal->judul, $proposal->total_anggaran),
                        'gembala' => WaTemplate::proposalMenungguGembala($next->approver->name, $proposal->departemen->nama_departemen, $proposal->judul, $proposal->total_anggaran),
                        default => WaTemplate::proposalBaru($next->approver->name, $proposal->departemen->nama_departemen, $proposal->judul, $proposal->total_anggaran),
                    };
                    app(NotificationService::class)->queue($next->approver->nohp, $message, 'Proposal menunggu persetujuan', $next->approver_id, 'proposal-approve-'.$flow->id.'-'.$flow->updated_at->timestamp);
                } else {
                    foreach (User::where('role', 'keuangan')->where('is_active', 1)->get() as $finance) {
                        app(NotificationService::class)->queue($finance->nohp, 'Proposal #'.$proposal->id.' '.$proposal->judul.' telah disetujui dan siap diproses keuangan.', 'Proposal disetujui', $finance->id, 'proposal-ready-'.$proposal->id.'-'.$flow->id.'-'.$finance->id);
                    }
                }
            }
        }, 3);

        return back()->with('success', 'Keputusan disimpan. Status pengiriman pesan dicatat secara terpisah.');
    }

    public function history(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:150', 'status' => 'nullable|in:disetujui,ditolak,dicairkan']);
        $q = Access::proposals($request->user())->with(['user', 'departemen', 'approval_flows']);
        if (in_array($request->user()->role, ['departemen_head', 'group_head', 'gembala'])) {
            $q->whereHas('approval_flows', fn ($f) => $f->where('approver_id', $request->user()->id)->where('status', $request->status === 'ditolak' ? 'ditolak' : 'disetujui'));
        } else {
            $q->whereIn('status', $request->status === 'ditolak' ? ['revisi', 'ditolak'] : ['disetujui_senior_pastor', 'dicairkan', 'dicairkan_sebagian']);
        }
        if ($request->status === 'dicairkan') {
            $q->whereIn('status', ['dicairkan', 'dicairkan_sebagian']);
        }
        if ($request->filled('search')) {
            $q->where(fn ($q) => $q->where('judul', 'like', '%'.$request->search.'%')->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$request->search.'%')));
        }

        return view('backend.approval.history', ['historyApprovals' => $q->latest('updated_at')->paginate(10)->withQueryString()]);
    }

    public function notifKeuangan(Request $request, $proposalId)
    {
        return DB::transaction(function () use ($request, $proposalId) {
            $proposal = Proposal::with(['departemen', 'user'])->lockForUpdate()->findOrFail($proposalId);
            Access::viewProposal($request->user(), $proposal);
            abort_unless($request->user()->role === 'group_head', 403, 'Hanya kepala grup yang dapat meminta konfirmasi ini.');
            $flows = $proposal->approval_flows()->lockForUpdate()->get();
            $flow = $flows->firstWhere('tahap', 'group_head');
            abort_unless($flow && (int) $flow->approver_id === (int) $request->user()->id, 403, 'Anda bukan approver kepala grup yang ditetapkan.');
            abort_unless($flows->pluck('tahap')->all() === ['departemen_head', 'group_head', 'gembala'], 409, 'Alur persetujuan belum lengkap. Hubungi admin untuk revisi.');
            $priorApproved = ! $flows->where('urutan', '<', $flow->urutan)->contains(fn ($previous) => $previous->status !== 'disetujui');
            $confirmation = $proposal->status === 'disetujui_mh' && $flow->status === 'pending' && $priorApproved;
            $reminder = in_array($proposal->status, ['disetujui_senior_pastor', 'dicairkan_sebagian'], true) && $flow->status === 'disetujui' && $flows->every(fn ($stage) => $stage->status === 'disetujui');
            abort_unless($confirmation || $reminder, 409, 'Notifikasi belum dapat diminta: tunggu persetujuan kepala departemen dan pastikan proposal masih pada tahap yang sesuai.');
            $finances = User::where('role', 'keuangan')->where('is_active', 1)->get();
            if ($finances->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Belum ada akun keuangan aktif. Hubungi admin.'], 422);
            }
            $message = $confirmation
                ? WaTemplate::konfirmasiDanaKeuangan($request->user()->name, $proposal->judul, $proposal->departemen->nama_departemen, $proposal->total_anggaran)
                : WaTemplate::proposalSiapDicairkan($proposal->judul, $proposal->total_anggaran);
            foreach ($finances as $finance) {
                app(NotificationService::class)->queue($finance->nohp, $message,
                    $confirmation ? 'Konfirmasi dana sebelum persetujuan kepala grup' : 'Pengingat pencairan keuangan', $finance->id,
                    'finance-'.($confirmation ? 'confirm' : 'remind').'-'.$flow->id.'-'.($flow->updated_at?->format('Uu') ?? 'legacy').'-'.$finance->id);
            }

            return response()->json(['success' => true, 'message' => 'Permintaan notifikasi tercatat. Admin/keuangan dapat memantau pengiriman pada Status Notifikasi. Ini belum berarti keuangan sudah mengonfirmasi dana.']);
        }, 3);
    }
}
