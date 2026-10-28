<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PencairanDana;
use App\Models\Proposal;
use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalProposalDisetujui = Proposal::whereIn('status', ['disetujui_senior_pastor', 'dicairkan'])->count();

        // Total dana dicairkan per rekening
        $danaPerRekening = Rekening::leftJoin('pencairan_dana', 'rekening.id', '=', 'pencairan_dana.rekening_id')
            ->select(
                'rekening.nama_rekening',
                DB::raw('COALESCE(SUM(pencairan_dana.jumlah_dana), 0) as total_dana')
            )
            ->groupBy('rekening.nama_rekening')
            ->get();

        $totalDanaSemua = $danaPerRekening->sum('total_dana');

        // 🔥 Total dana dicairkan per departemen
        $danaPerDepartemen = PencairanDana::join('proposal', 'pencairan_dana.proposal_id', '=', 'proposal.id')
            ->join('departemen', 'proposal.departemen_id', '=', 'departemen.id')
            ->select(
                'departemen.nama_departemen',
                DB::raw('SUM(pencairan_dana.jumlah_dana) as total_dana')
            )
            ->groupBy('departemen.nama_departemen')
            ->get();

        return view('backend.dashboard', compact(
            'totalProposalDisetujui',
            'danaPerRekening',
            'totalDanaSemua',
            'danaPerDepartemen'
        ));
    }

    public function dataPencairan()
    {
        return response()->json(PencairanDana::selectRaw('DATE(created_at) AS tanggal, SUM(jumlah_dana) AS total')->groupByRaw('DATE(created_at)')->orderBy('tanggal')->get());
    }
}
