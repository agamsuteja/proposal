<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\PencairanDana;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\LaporanExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Departemen;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Session telah habis. Silakan login kembali.');
        }

        $role = $user->role;

        // Filter tanggal
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Filter departemen
        $departemenId = $request->departemen_id;

        // Dropdown departemen
        $departemens = Departemen::orderBy('nama_departemen')->get();

        // ROLE KEUANGAN
        if ($role === 'keuangan') {
            $data = PencairanDana::with(['proposal.user', 'proposal.departemen', 'rekening'])
                ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
                ->when($departemenId, fn($q) =>
                    $q->whereHas('proposal', fn($p) => $p->where('departemen_id', $departemenId))
                )
                ->latest()
                ->paginate(10)
                ->appends($request->only(['start_date', 'end_date', 'departemen_id']));
        }

        // ROLE MH / GH / GP
        elseif (in_array($role, ['departemen_head', 'group_head', 'gembala'])) {

            $query = Proposal::with(['user', 'departemen'])
                ->whereIn('status', [
                    'disetujui_mh', 'disetujui_gh', 'disetujui_senior_pastor',
                    'revisi', 'ditolak', 'dicairkan'
                ])
                ->when($startDate, fn($q) => $q->whereDate('tanggal_pengajuan', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('tanggal_pengajuan', '<=', $endDate))
                ->when($departemenId, fn($q) => $q->where('departemen_id', $departemenId));

            if ($role === 'departemen_head') {
                $query->where('departemen_id', $user->departemen_id);
            } elseif ($role === 'group_head') {
                $query->whereHas('departemen', fn($d) => $d->where('grup_id', $user->grup_id));
            }

            $data = $query->latest()->paginate(10)
                ->appends($request->only(['start_date', 'end_date', 'departemen_id']));
        }

        // ROLE ADMIN
        else {
            $data = Proposal::with(['user', 'departemen'])
                ->when($startDate, fn($q) => $q->whereDate('tanggal_pengajuan', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('tanggal_pengajuan', '<=', $endDate))
                ->when($departemenId, fn($q) => $q->where('departemen_id', $departemenId))
                ->latest()
                ->paginate(10)
                ->appends($request->only(['start_date', 'end_date', 'departemen_id']));
        }

        return view('backend.laporan.index', compact('data', 'role', 'departemens'));
    }



    // 🔹 Export Excel
    public function exportExcel()
    {
        return Excel::download(new LaporanExport, 'laporan_proposal.xlsx');
    }


    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        $role = $user->role;

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $departemenId = $request->departemen_id;

        // ==========================================
        // ROLE KEUANGAN (tabel pencairan)
        // ==========================================
        if ($role === 'keuangan') {

            $data = PencairanDana::with(['proposal.user', 'proposal.departemen', 'rekening'])
                ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
                ->when($departemenId, fn($q) =>
                    $q->whereHas('proposal', fn($p) => $p->where('departemen_id', $departemenId))
                )
                ->latest()
                ->get();
        }

        // ==========================================
        // ROLE ADMIN / MH / GH / GP (tabel proposal)
        // ==========================================
        else {

            $data = Proposal::with(['user', 'departemen'])
                ->when($startDate, fn($q) => $q->whereDate('tanggal_pengajuan', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('tanggal_pengajuan', '<=', $endDate))
                ->when($departemenId, fn($q) => $q->where('departemen_id', $departemenId))
                ->latest()
                ->get();
        }

        $pdf = Pdf::loadView('backend.laporan.export_pdf', compact('data', 'role'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan_proposal.pdf');
    }


}
