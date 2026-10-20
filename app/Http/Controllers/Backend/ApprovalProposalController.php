<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ApprovalProposal;
use App\Models\Proposal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ApprovalProposalController extends Controller
{
    // =========================================================================
    // INDEX — Daftar approval pending & history milik user yang login
    // =========================================================================
    public function index()
    {
        $user = Auth::user();

        $query = ApprovalProposal::with([
            'proposal.user',
            'proposal.departemen',
            'proposal.items',
            'proposal.approval_flows',
        ])->where('approver_id', $user->id);

        // Filter berdasarkan role
        if (in_array($user->role, ['departemen_head', 'karyawan'])) {
            $query->whereHas('proposal', fn($q) => $q->where('departemen_id', $user->departemen_id));
        } elseif ($user->role === 'group_head') {
            $departemenIds = \App\Models\Departemen::where('grup_id', $user->grup_id)->pluck('id');
            $query->whereHas('proposal', fn($q) => $q->whereIn('departemen_id', $departemenIds));
        }

        $pendingApprovals = $query
            ->clone()
            ->where('status', 'pending')
            ->get()
            ->filter(fn($flow) => $flow->proposal && $flow->proposal->approval_flows)
            ->map(function ($flow) {
                $flows = $flow->proposal->approval_flows->sortBy('urutan');
                $prev = $flows->firstWhere('urutan', $flow->urutan - 1);
                $flow->can_approve = !$prev || $prev->status === 'disetujui';
                $flow->reason = !$flow->can_approve ? 'Menunggu Approval Sebelumnya' : null;
                return $flow;
            });

        $historyApprovals = $query
            ->clone()
            ->whereIn('status', [
                'disetujui',
                'ditolak',
                'disetujui_mh',
                'disetujui_gh',
                'disetujui_pastor',
                'disetujui_senior_pastor',
                'dicairkan',
                'revisi',
            ])
            ->orderByDesc('updated_at')
            ->paginate(10);

        return view('backend.approval.index', compact('pendingApprovals', 'historyApprovals'));
    }

    // =========================================================================
    // SHOW — Detail proposal untuk di-approve
    // =========================================================================
    public function show($id)
    {
        $proposal = Proposal::with([
            'user',
            'departemen',
            'items',
            'approval_flows.approver',
        ])->findOrFail($id);

        if (!$proposal->approval_flows || $proposal->approval_flows->isEmpty()) {
            abort(404, 'Proposal tidak memiliki alur approval.');
        }

        $proposal->approval_flows = $proposal->approval_flows->sortBy('urutan')->values();
        $userId = Auth::id();

        $currentFlow = $proposal->approval_flows->firstWhere('approver_id', $userId);

        if (!$currentFlow) {
            abort(403, 'Anda tidak memiliki akses ke proposal ini.');
        }

        $prevFlow = $proposal->approval_flows->firstWhere('urutan', $currentFlow->urutan - 1);
        $canApprove = !$prevFlow || $prevFlow->status === 'disetujui';

        if (!$canApprove) {
            session()->flash('warning', 'Menunggu persetujuan dari tahap sebelumnya sebelum Anda dapat menindaklanjuti proposal ini.');
        }

        return view('backend.approval.show', compact('proposal', 'currentFlow', 'canApprove'));
    }

    // =========================================================================
    // UPDATE — Setujui atau tolak proposal
    // =========================================================================
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'catatan' => 'nullable|string|max:1000',
        ]);

        $approval = null;
        $proposal = null;

        DB::transaction(function () use ($request, $id, &$approval, &$proposal) {
            $approval = ApprovalProposal::findOrFail($id);
            $proposal = $approval->proposal;

            // Guard: jangan proses ulang jika sudah bukan pending
            if ($approval->status !== 'pending') {
                return;
            }

            $approval->update([
                'status' => $request->status,
                'catatan' => $request->catatan,
                'tanggal_approve' => now(),
            ]);

            // DITOLAK → set proposal ke revisi
            if ($request->status === 'ditolak') {
                $proposal->update(['status' => 'revisi']);
                return;
            }

            // DISETUJUI → cek apakah semua approval sudah selesai
            $pendingCount = ApprovalProposal::where('proposal_id', $proposal->id)
                ->where('status', 'pending')
                ->count();

            if ($pendingCount == 0) {
                $proposal->update(['status' => 'disetujui_senior_pastor']);
            } else {
                if ($approval->tahap === 'departemen_head') {
                    $proposal->update(['status' => 'disetujui_mh']);
                } elseif ($approval->tahap === 'group_head') {
                    $proposal->update(['status' => 'disetujui_gh']);
                }
            }
        });

        // Ambil data terbaru setelah transaction commit
        $approval = ApprovalProposal::findOrFail($id);
        $proposal = $approval->proposal->fresh();

        if ($request->status === 'ditolak') {
            $this->kirimNotifRevisiKePemohon($approval, $proposal);
            return back()->with('success', 'Proposal dikembalikan untuk revisi.');
        }

        if ($request->status === 'disetujui') {
            $this->kirimNotifWhatsApp($approval, $proposal);
        }

        return back()->with('success', 'Status proposal berhasil diperbarui!');
    }

    // =========================================================================
    // HISTORY — Riwayat approval per role
    // =========================================================================
    public function history(Request $request)
    {
        $user = auth()->user();
        $search = $request->search;
        $statusFilter = $request->status;

        $history = Proposal::with(['user', 'departemen', 'approval_flows'])
            ->when($user->role === 'keuangan', function ($q) use ($statusFilter) {
                $q->whereIn('status', ['disetujui_senior_pastor', 'dicairkan', 'selesai']);

                if ($statusFilter === 'disetujui') {
                    $q->where('status', 'disetujui_senior_pastor');
                } elseif ($statusFilter === 'dicairkan') {
                    $q->where('status', 'dicairkan');
                }
            })
            ->when(in_array($user->role, ['departemen_head', 'group_head', 'gembala']), function ($q) use ($user, $statusFilter) {
                $q->whereHas('approval_flows', function ($subQ) use ($user, $statusFilter) {
                    $subQ->where('approver_id', $user->id);

                    if ($statusFilter === 'disetujui') {
                        $subQ->whereIn('status', [
                            'disetujui',
                            'disetujui_mh',
                            'disetujui_gh',
                            'disetujui_pastor',
                            'disetujui_senior_pastor',
                            'dicairkan',
                        ]);
                    } elseif ($statusFilter === 'ditolak') {
                        $subQ->where('status', 'revisi');
                    } else {
                        $subQ->whereIn('status', [
                            'disetujui',
                            'disetujui_mh',
                            'disetujui_gh',
                            'disetujui_pastor',
                            'disetujui_senior_pastor',
                            'revisi',
                            'dicairkan',
                        ]);
                    }
                });
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($x) use ($search) {
                    $x
                        ->where('judul', 'like', "%$search%")
                        ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%$search%"));
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(5);

        return view('backend.approval.history', ['historyApprovals' => $history]);
    }

    // =========================================================================
    // NOTIF KEUANGAN — Kirim WA ke tim keuangan (dipanggil via AJAX)
    // =========================================================================
    public function notifKeuangan(Request $request, $proposalId)
    {
        try {
            // Hanya group_head yang boleh
            if (Auth::user()->role !== 'group_head') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya Group Head yang dapat mengirim notifikasi ini.',
                ], 403);
            }

            $proposal = Proposal::with(['departemen', 'user'])->findOrFail($proposalId);
            $sender = Auth::user();

            // Cari user dengan role keuangan
            $keuangan = User::where('role', 'keuangan')->first();

            if (!$keuangan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun tim keuangan tidak ditemukan di sistem.',
                ], 404);
            }

            if (!$keuangan->nohp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor HP tim keuangan belum diisi di sistem.',
                ], 404);
            }

            $judul = $proposal->judul;
            $total = number_format($proposal->total_anggaran, 0, ',', '.');
            $dept = $proposal->departemen->nama_departemen ?? '-';

            $pesan = \App\Services\WaTemplate::konfirmasiDanaKeuangan($sender->name, $judul, $dept, $total);

            $berhasil = $this->sendViaFonnte($keuangan->nohp, $pesan);

            if ($berhasil) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notifikasi WhatsApp berhasil dikirim ke tim keuangan.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pesan WhatsApp. Silakan cek log server.',
            ], 500);
        } catch (\Exception $e) {
            \Log::error('notifKeuangan error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Kirim WA ke approver berikutnya setelah disetujui
     */
    private function kirimNotifWhatsApp($approval, $proposal)
    {
        if (in_array($approval->status, ['ditolak', 'revisi'])) {
            \Log::info('WA tidak dikirim karena proposal ditolak/revisi.');
            return;
        }

        $nextApprover = null;
        $pesan = null;
        $judul = $proposal->judul;
        $total = number_format($proposal->total_anggaran, 0, ',', '.');

        if ($approval->tahap === 'departemen_head') {
            $nextApprover = User::where('role', 'group_head')
                ->where('grup_id', $proposal->departemen->grup_id)
                ->first();

            if ($nextApprover) {
                $pesan = \App\Services\WaTemplate::proposalMasukGroupHead($nextApprover->name, $proposal->departemen->nama_departemen, $judul, $total);
            }
        } elseif ($approval->tahap === 'group_head') {
            $nextApprover = User::where('role', 'gembala')->first();

            if ($nextApprover) {
                $pesan = \App\Services\WaTemplate::proposalMenungguGembala($nextApprover->name, $proposal->departemen->nama_departemen, $judul, $total);
            }
        } elseif ($approval->tahap === 'gembala') {
            $nextApprover = User::where('role', 'keuangan')->first();

            if ($nextApprover) {
                $pesan = \App\Services\WaTemplate::proposalSiapDicairkan($judul, $total);
            }
        }

        if ($nextApprover && $nextApprover->nohp && $pesan) {
            \Log::info("Mengirim WA ke {$nextApprover->name} ({$nextApprover->nohp})");
            $this->sendViaFonnte($nextApprover->nohp, $pesan);
        } else {
            \Log::warning('Tidak bisa kirim WA: nextApprover=' . ($nextApprover ? 'found' : 'null')
                . ', nohp=' . ($nextApprover ? $nextApprover->nohp : 'null'));
        }
    }

    /**
     * Kirim WA ke pemohon saat proposal diminta revisi
     */
    private function kirimNotifRevisiKePemohon($approval, $proposal)
    {
        try {
            $pemohon = $proposal->user;
            if (!$pemohon || !$pemohon->nohp)
                return;

            $judul = $proposal->judul;
            $catatan = $approval->catatan ?: '-';

            $pesan = \App\Services\WaTemplate::revisiKePemohon($pemohon->name, $judul, $catatan);

            $this->sendViaFonnte($pemohon->nohp, $pesan);
        } catch (\Exception $e) {
            \Log::error('Gagal kirim WA revisi: ' . $e->getMessage());
        }
    }

    /**
     * Kirim ulang notif ke Group Head setelah proposal direvisi & diajukan ulang
     */
    private function kirimUlangKeGroupHead($proposal)
    {
        $groupHead = User::where('role', 'group_head')
            ->where('grup_id', $proposal->departemen->grup_id)
            ->first();

        if (!$groupHead || !$groupHead->nohp)
            return;

        $judul = $proposal->judul;
        $total = number_format($proposal->total_anggaran, 0, ',', '.');

        $pesan = \App\Services\WaTemplate::diajukanKembali($groupHead->name, $proposal->departemen->nama_departemen, $judul, $total);

        $this->sendViaFonnte($groupHead->nohp, $pesan);
    }

    /**
     * Kirim WhatsApp via Fonnte (lihat App\Services\FonnteService)
     */
    private function sendViaFonnte($nohp, $pesan)
    {
        return app(\App\Services\FonnteService::class)->send($nohp, $pesan);
    }
}
