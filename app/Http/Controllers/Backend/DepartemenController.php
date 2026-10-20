<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Grup;
use Illuminate\Http\Request;

class DepartemenController extends Controller
{
    public function index()
    {
        $departemen = Departemen::with('grup')->latest()->get();
        
        return view('backend.departemen.index', compact('departemen'));
    }

    public function create()
    {
        $grup = Grup::orderBy('nama_grup')->get();
        return view('backend.departemen.create', compact('grup'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'grup_id' => 'required|exists:grup,id',
            'nama_departemen' => 'required|string|max:100',
        ]);

        Departemen::create([
            'grup_id' => $request->grup_id,
            'nama_departemen' => $request->nama_departemen,
            'deskripsi' => $request->deskripsi,
        ]);

        return redirect()->route('admin.departemen.index')
            ->with('success', 'Departemen berhasil ditambahkan');
    }

    public function edit($id)
    {
        $departemen = Departemen::findOrFail($id);
        $grup = Grup::orderBy('nama_grup')->get();
        return view('backend.departemen.edit', compact('departemen', 'grup'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'grup_id' => 'required|exists:grup,id',
            'nama_departemen' => 'required|string|max:100',
        ]);

        $departemen = Departemen::findOrFail($id);
        $departemen->update($request->only(['grup_id', 'nama_departemen', 'deskripsi']));

        return redirect()->route('admin.departemen.index')
            ->with('success', 'Departemen berhasil diperbarui');
    }

    public function destroy($id)
    {
        $departemen = Departemen::findOrFail($id);
        $departemen->delete();

        return redirect()->route('admin.departemen.index')
            ->with('success', 'Departemen berhasil dihapus');
    }
}
