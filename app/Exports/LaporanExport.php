<?php

namespace App\Exports;

use App\Models\Proposal;
use App\Models\PencairanDana;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;

class LaporanExport implements FromView
{
    public function view(): View
    {
        $user = Auth::user();
        $role = $user->role;

        if ($role === 'keuangan') {
            $data = PencairanDana::with(['proposal.user', 'proposal.departemen', 'rekening'])->latest()->get();
        } elseif (in_array($role, ['departemen_head', 'group_head', 'gembala'])) {
            $data = Proposal::with(['user', 'departemen'])
                ->whereIn('status', ['disetujui_pastor', 'disetujui_gh', 'disetujui_mh', 'revisi', 'ditolak', 'dicairkan'])
                ->get();
        } else {
            $data = Proposal::with(['user', 'departemen'])->latest()->get();
        }

        return view('backend.laporan.export_excel', compact('data', 'role'));
    }
}
