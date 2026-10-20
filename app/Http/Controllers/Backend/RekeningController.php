<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Rekening;
use Illuminate\Http\Request;

class RekeningController extends Controller
{
    // public function index()
    // {
    //     $rekenings = Rekening::orderBy('nama_rekening')->get();

    //     return view('backend.rekening.index', compact('rekenings'));
    // }

    public function index()
    {
        $rekenings = Rekening::orderBy('nama_rekening')->paginate(5);

        return view('backend.rekening.index', compact('rekenings'));
    }


    public function create()
    {
        return view('backend.rekening.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_rekening' => 'required|string|max:100',
            'saldo' => 'required|numeric|min:0',
        ]);

        Rekening::create($request->all());
        return redirect()->route('admin.rekening.index')->with('success', 'Rekening berhasil ditambahkan!');
    }

    public function edit(Rekening $rekening)
    {
        return view('backend.rekening.edit', compact('rekening'));
    }

    public function update(Request $request, Rekening $rekening)
    {
        $request->validate([
            'nama_rekening' => 'required|string|max:100',
            'saldo' => 'required|numeric|min:0',
        ]);

        $rekening->update($request->all());
        return redirect()->route('admin.rekening.index')->with('success', 'Rekening berhasil diperbarui!');
    }

    public function destroy(Rekening $rekening)
    {
        $rekening->delete();
        return redirect()->route('admin.rekening.index')->with('success', 'Rekening berhasil dihapus!');
    }
}
