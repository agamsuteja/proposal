<?php

namespace App\Http\Controllers\Backend;

use App\Exports\LaporanExport;
use App\Http\Controllers\Controller;
use App\Services\ReportQuery;
use App\Support\Access;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(Request $request, ReportQuery $reports)
    {
        return view('backend.laporan.index', ['data' => $reports->query($request)->paginate(10)->withQueryString(),
            'role' => $request->user()->role, 'departemens' => Access::departments($request->user())->orderBy('nama_departemen')->get()]);
    }

    public function exportExcel(Request $request, ReportQuery $reports)
    {
        return Excel::download(new LaporanExport($reports->query($request)->get(), $request->user()->role), 'laporan_proposal.xlsx');
    }

    public function exportPdf(Request $request, ReportQuery $reports)
    {
        return Pdf::loadView('backend.laporan.export_pdf', ['data' => $reports->query($request)->get(), 'role' => $request->user()->role])->setPaper('a4', 'landscape')->download('laporan_proposal.pdf');
    }
}
