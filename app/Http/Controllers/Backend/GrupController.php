<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\Grup;
use Illuminate\Http\Request;

class GrupController extends Controller
{
    public function index()
    {
        $grup = Grup::latest()->get();
        return view('backend.grup.index', compact('grup'));
    }

    public function create()
    {
        return view('backend.grup.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_grup' => 'required|max:100',
        ]);

        Grup::create([
            'nama_grup' => $request->nama_grup,
            'deskripsi' => $request->deskripsi,
        ]);

        return redirect()->route('admin.grup.index')
            ->with('success', 'Grup berhasil ditambahkan');
    }

    public function edit($id)
    {
        $grup = Grup::findOrFail($id);
        return view('backend.grup.edit', compact('grup'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_grup' => 'required|max:100',
        ]);

        $grup = Grup::findOrFail($id);
        $grup->update($request->only('nama_grup', 'deskripsi'));

        return redirect()->route('admin.grup.index')
            ->with('success', 'Grup berhasil diperbarui');
    }

    public function destroy($id)
    {
        $grup = Grup::findOrFail($id);
        $grup->delete();

        return redirect()->route('admin.grup.index')
            ->with('success', 'Grup berhasil dihapus');
    }
}
