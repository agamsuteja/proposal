<?php

namespace App\Services;

use App\Models\PencairanDana;
use App\Support\Access;
use Illuminate\Http\Request;

class ReportQuery
{
    public function query(Request $request)
    {
        $request->validate(['start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date', 'departemen_id' => 'nullable|integer']);
        $proposals = Access::proposals($request->user());
        if ($request->filled('departemen_id')) {
            abort_unless(Access::departments($request->user())->whereKey($request->departemen_id)->exists(), 403);
            $proposals->where('departemen_id', $request->departemen_id);
        }
        if ($request->user()->role === 'keuangan') {
            $q = PencairanDana::with(['proposal.user', 'proposal.departemen', 'rekening'])->whereIn('proposal_id', $proposals->select('id'));
            $date = 'created_at';
        } else {
            $q = $proposals->with(['user', 'departemen']);
            $date = 'tanggal_pengajuan';
        }

        return $q->when($request->filled('start_date'), fn ($q) => $q->whereDate($date, '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate($date, '<=', $request->end_date))->latest();
    }
}
