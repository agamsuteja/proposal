<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MaintenanceProposalController extends Controller
{
        public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:150',
            'tanggal_pengajuan' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.nama_item' => 'required|string',
            'items.*.estimasi_biaya' => 'required|numeric|min:1',
        ]);

        DB::transaction(function () use ($request) {

            $proposal = MaintenanceProposal::create([
                'kode_proposal' => 'MTN-' . time(),
                'user_id' => auth()->id(),
                'departemen_id' => auth()->user()->departemen_id,
                'judul' => $request->judul,
                'tujuan' => $request->tujuan,
                'tanggal_pengajuan' => $request->tanggal_pengajuan,
                'keterangan' => $request->keterangan,
                'total_anggaran' => collect($request->items)->sum('estimasi_biaya'),
                'status' => 'diajukan'
            ]);

            foreach ($request->items as $item) {
                MaintenanceItem::create([
                    'maintenance_proposal_id' => $proposal->id,
                    'nama_item' => $item['nama_item'],
                    'lokasi' => $item['lokasi'] ?? null,
                    'jenis_perawatan' => $item['jenis_perawatan'] ?? null,
                    'deskripsi_kerusakan' => $item['deskripsi_kerusakan'] ?? null,
                    'estimasi_biaya' => $item['estimasi_biaya'],
                ]);
            }

        });

        return redirect()->route('admin.maintenance.index')
            ->with('success','Maintenance berhasil diajukan');
    }

}
