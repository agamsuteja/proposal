<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\DepartmentBudget;
use App\Models\Grup;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        DB::transaction(function () use ($id) {
            $departemen = Departemen::lockForUpdate()->findOrFail($id);
            if (User::where('departemen_id', $departemen->id)->exists() || Proposal::where('departemen_id', $departemen->id)->exists() || DepartmentBudget::where('departemen_id', $departemen->id)->exists()) {
                throw ValidationException::withMessages(['departemen' => 'Departemen masih terkait pengguna, proposal, atau budget dan tidak dapat dihapus.']);
            }
            $departemen->delete();
        }, 3);

        return redirect()->route('admin.departemen.index')
            ->with('success', 'Departemen berhasil dihapus');
    }
}
