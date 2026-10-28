<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Grup;
use App\Models\User;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        return view('backend.user.index', ['users' => User::with(['departemen', 'grup'])->latest()->get()]);
    }

    public function create()
    {
        return view('backend.user.create', $this->options());
    }

    public function edit($id)
    {
        return view('backend.user.edit', ['user' => User::findOrFail($id)] + $this->options());
    }

    private function options(): array
    {
        return ['departemen' => Departemen::orderBy('nama_departemen')->get(), 'grup' => Grup::orderBy('nama_grup')->get(), 'roles' => Access::ROLES];
    }

    private function data(Request $request, ?User $user = null): array
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => ['required', 'email', 'safe_email', 'max:100', Rule::unique('users')->ignore($user)],
            'nohp' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in(Access::ROLES)], 'is_active' => 'sometimes|boolean',
            'password' => ($user ? 'nullable' : 'required').'|string|min:10',
            'grup_id' => 'required_if:role,group_head|nullable|integer|exists:grup,id',
            'departemen_id' => 'required_if:role,departemen_head,karyawan|nullable|integer|exists:departemen,id']);
        if (in_array($data['role'], ['karyawan', 'departemen_head'])) {
            $data['grup_id'] = Departemen::findOrFail($data['departemen_id'])->grup_id;
        } elseif ($data['role'] === 'group_head') {
            $data['departemen_id'] = null;
        } else {
            $data['departemen_id'] = null;
            $data['grup_id'] = null;
        }
        $data['is_active'] = $request->boolean('is_active', $user ? (bool) $user->is_active : true);
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }

    public function store(Request $request)
    {
        $user = new User($this->data($request));
        $user->forceFill(['registration_verified_at' => now()])->save();

        return redirect()->route('admin.user.index')->with('success', 'Pengguna berhasil ditambahkan oleh admin.');
    }

    public function update(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            // Lock admins in a stable order before enforcing the last-active-admin invariant.
            User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $user = User::lockForUpdate()->findOrFail($id);
            $data = $this->data($request, $user);
            if ($user->id === auth()->id() && ($data['role'] !== 'admin' || ! $data['is_active'])) {
                throw ValidationException::withMessages(['role' => 'Admin tidak dapat menurunkan role atau menonaktifkan dirinya sendiri.']);
            }
            if ($data['is_active'] && ! $user->is_active && ! $user->registration_verified_at) {
                throw ValidationException::withMessages(['is_active' => 'Akun registrasi harus memverifikasi OTP terlebih dahulu.']);
            }
            if ($user->role === 'admin' && ($data['role'] !== 'admin' || ! $data['is_active']) && User::where('role', 'admin')->where('is_active', 1)->where('id', '!=', $user->id)->count() === 0) {
                throw ValidationException::withMessages(['role' => 'Harus ada minimal satu admin aktif.']);
            }
            $user->update($data);
        });

        return redirect()->route('admin.user.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy($id)
    {
        // Preserve ownership/approval/audit history rather than cascading deletion.
        abort_if((int) $id === auth()->id(), 409, 'Tidak dapat menonaktifkan diri sendiri.');
        DB::transaction(function () use ($id) {
            User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $user = User::lockForUpdate()->findOrFail($id);
            if ($user->role === 'admin' && User::where('role', 'admin')->where('is_active', 1)->where('id', '!=', $user->id)->count() === 0) {
                abort(409, 'Harus ada admin aktif.');
            }
            $user->update(['is_active' => 0]);
        }, 3);

        return back()->with('success', 'Akun dinonaktifkan; riwayat transaksi tetap tersimpan.');
    }
}
