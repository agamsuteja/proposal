<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Departemen;
use App\Models\Grup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['departemen', 'grup'])->latest()->get();
        return view('backend.user.index', compact('users'));
    }

    public function create()
    {
        $departemen = Departemen::orderBy('nama_departemen')->get();
        $grup = Grup::orderBy('nama_grup')->get();
        $roles = ['karyawan', 'departemen_head', 'group_head', 'gembala', 'keuangan', 'admin'];

        return view('backend.user.create', compact('departemen', 'grup', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email',
            'nohp'         => 'required|nohp|unique:users,nohp',
            'password'      => 'required|min:6',
            'role'          => 'required',
            'grup_id'       => 'required_if:role,group_head|nullable|exists:grup,id',
            'departemen_id' => 'required_if:role,departemen_head,karyawan|nullable|exists:departemen,id',
        ], [
            'grup_id.required_if'       => 'Grup wajib dipilih jika role adalah Group Head.',
            'departemen_id.required_if' => 'Departemen wajib dipilih jika role adalah Departemen Head atau Karyawan.',
        ]);

        // Simpan user
        User::create([
            'departemen_id' => $request->departemen_id,
            'grup_id'       => $request->grup_id,
            'name'          => $request->name,
            'nohp'          => $request->nohp,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'role'          => $request->role,
            'is_active'     => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.user.index')
            ->with('success', 'User berhasil ditambahkan');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $departemen = Departemen::orderBy('nama_departemen')->get();
        $grup = Grup::orderBy('nama_grup')->get();
        $roles = ['karyawan', 'departemen_head', 'group_head', 'gembala', 'keuangan', 'admin'];

        return view('backend.user.edit', compact('user', 'departemen', 'grup', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email,' . $id,
            'nohp'          => 'required|string|max:13',
            'role'          => 'required',
            'grup_id'       => 'required_if:role,group_head|nullable|exists:grup,id',
            'departemen_id' => 'required_if:role,departemen_head,karyawan|nullable|exists:departemen,id',
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'departemen_id' => $request->departemen_id,
            'grup_id'       => $request->grup_id,
            'name'          => $request->name,
            'email'         => $request->email,
            'nohp'          => $request->nohp,
            'role'          => $request->role,
            'is_active'     => $request->is_active ?? true,
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        return redirect()->route('admin.user.index')
            ->with('success', 'User berhasil diperbarui');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.user.index')
            ->with('success', 'User berhasil dihapus');
    }


  

}
