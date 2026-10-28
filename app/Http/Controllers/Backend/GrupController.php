<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Grup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        DB::transaction(function () use ($id) {
            $grup = Grup::lockForUpdate()->findOrFail($id);
            if (Departemen::where('grup_id', $grup->id)->exists() || User::where('grup_id', $grup->id)->exists()) {
                throw ValidationException::withMessages(['grup' => 'Grup masih terkait departemen atau pengguna dan tidak dapat dihapus.']);
            }
            $grup->delete();
        }, 3);

        return redirect()->route('admin.grup.index')
            ->with('success', 'Grup berhasil dihapus');
    }
}
