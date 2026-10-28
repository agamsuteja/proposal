<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;

class MaintenanceProposalController extends ProposalController
{
    public function index()
    {
        return redirect()->route('admin.proposal.index', ['jenis' => 'maintenance']);
    }

    public function create()
    {
        return redirect()->route('admin.proposal.create', ['jenis' => 'maintenance']);
    }

    public function store(Request $request)
    {
        $request->merge(['jenis' => 'maintenance', 'items' => collect($request->input('items', []))->map(fn ($i) => [
            'nama_barang' => $i['nama_barang'] ?? $i['nama_item'] ?? '', 'anggaran' => $i['anggaran'] ?? $i['estimasi_biaya'] ?? '',
            'tujuan_pembelian' => $i['tujuan_pembelian'] ?? $i['deskripsi_kerusakan'] ?? null,
        ])->all()]);

        return parent::store($request);
    }
}
