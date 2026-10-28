<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthChallenge;
use App\Models\Departemen;
use App\Models\Grup;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\WaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('backend.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email|safe_email', 'password' => 'required|string']);
        if (! Auth::attempt($credentials)) {
            return back()->with('error', 'Email atau password salah.')->withInput($request->only('email'));
        }
        if (! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->with('error', 'Akun belum aktif. Selesaikan verifikasi dan tunggu persetujuan admin.')->withInput($request->only('email'));
        }
        $request->session()->regenerate();
        $request->session()->put('auth_password_fingerprint', hash('sha256', Auth::user()->getAuthPassword()));
        $route = match (Auth::user()->role) {
            'karyawan' => 'admin.proposal.index', 'departemen_head','group_head','gembala' => 'admin.approval.index',
            default => 'admin.dashboard',
        };

        return redirect()->route($route);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function forgotPasswordForm()
    {
        return view('backend.auth.forgot-password');
    }

    public function forgotPasswordSend(Request $request)
    {
        $request->validate(['email' => 'required|email|safe_email', 'nohp' => 'required|string|max:20']);
        $user = User::where('email', $request->email)->where('nohp', $request->nohp)->where('is_active', 1)->first();
        if ($user) {
            $this->issue($user, 'password_reset');
        }

        return redirect()->route('admin.reset.form', ['email' => $request->email])->with('success', 'Permintaan reset diterima. Jika data cocok, sistem mencoba mengirim kode ke WhatsApp terdaftar. Jika belum diterima, coba lagi beberapa saat.');
    }

    public function resetPasswordForm(Request $request)
    {
        $request->validate(['email' => 'required|email|safe_email']);

        return view('backend.auth.reset-password', ['email' => $request->email]);
    }

    public function resetPasswordSubmit(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|safe_email', 'otp' => 'required|digits:6', 'password' => 'required|string|min:10|confirmed']);
        DB::transaction(function () use ($data) {
            $user = User::where('email', $data['email'])->lockForUpdate()->first();
            if (! $user || ! $user->is_active) {
                $this->invalidOtp();
            }
            $this->consume($user, 'password_reset', $data['otp']);
            $user->update(['password' => Hash::make($data['password'])]);
        });
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Password diperbarui. Semua sesi harus login kembali.');
    }

    public function showRegisterForm()
    {
        return view('backend.auth.register', ['departemens' => Departemen::orderBy('nama_departemen')->get(), 'grups' => Grup::orderBy('nama_grup')->get(), 'roles' => ['karyawan']]);
    }

    public function register(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|safe_email|max:100|unique:users,email',
            'nohp' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/', 'unique:users,nohp'],
            'password' => 'required|string|min:10|confirmed', 'role' => 'sometimes|in:karyawan', 'departemen_id' => 'required|integer|exists:departemen,id']);
        $department = Departemen::findOrFail($data['departemen_id']);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'nohp' => $data['nohp'],
            'password' => Hash::make($data['password']), 'role' => 'karyawan', 'is_active' => 0,
            'departemen_id' => $department->id, 'grup_id' => $department->grup_id]);
        $sent = $this->issue($user, 'registration');

        return redirect()->route('admin.register.verify.form', ['email' => $user->email])
            ->with($sent ? 'success' : 'error', $sent ? 'Kode verifikasi telah dikirim. Akun tetap menunggu persetujuan admin setelah verifikasi.' : 'Akun tersimpan, tetapi kode belum berhasil dikirim. Gunakan Kirim ulang kode.');
    }

    public function resendRegistrationOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|safe_email', 'nohp' => 'required|string|max:20']);
        $user = User::where('email', $request->email)->where('nohp', $request->nohp)->where('is_active', 0)->first();
        if ($user && ! $this->issue($user, 'registration')) {
            return back()->with('error', 'Kode belum berhasil dikirim. Silakan coba lagi nanti.');
        }

        return redirect()->route('admin.register.verify.form', ['email' => $request->email])->with('success', 'Jika data cocok dengan akun yang menunggu verifikasi, kode telah dikirim.');
    }

    public function showRegisterVerifyForm(Request $request)
    {
        $request->validate(['email' => 'required|email|safe_email']);

        return view('backend.auth.register-verify', ['email' => $request->email]);
    }

    public function registerVerify(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|safe_email', 'otp' => 'required|digits:6']);
        DB::transaction(function () use ($data) {
            $user = User::where('email', $data['email'])->where('is_active', 0)->lockForUpdate()->first();
            if (! $user) {
                $this->invalidOtp();
            }
            $this->consume($user, 'registration', $data['otp']);
            $user->forceFill(['registration_verified_at' => now()])->save();
        });

        return redirect()->route('admin.login')->with('success', 'Verifikasi berhasil. Akun menunggu persetujuan admin.');
    }

    public function pendingUsers()
    {
        return view('backend.admin.user-pending', ['users' => User::where('is_active', 0)->orderBy('created_at')->get()]);
    }

    public function approveUser($id)
    {
        DB::transaction(function () use ($id) {
            $user = User::lockForUpdate()->findOrFail($id);
            if (! $user->registration_verified_at) {
                throw ValidationException::withMessages(['user' => 'Pengguna belum menyelesaikan verifikasi OTP.']);
            }
            $user->update(['is_active' => 1]);
        });

        return back()->with('success', 'Pengguna berhasil diaktifkan.');
    }

    private function issue(User $user, string $purpose): bool
    {
        $otp = (string) random_int(100000, 999999);
        $expires = now()->addMinutes(10);
        DB::transaction(function () use ($user, $purpose, $otp, $expires) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            AuthChallenge::updateOrCreate(['user_id' => $user->id, 'purpose' => $purpose], ['otp_hash' => Hash::make($otp), 'expires_at' => $expires]);
        });
        $message = $purpose === 'registration' ? WaTemplate::otpRegistrasi($user->name, $otp) : WaTemplate::otpReset($user->name, $otp);
        $notification = app(NotificationService::class)->queue($user->nohp, $message, 'Kode verifikasi akun', $user->id, null, $expires, true);

        return $notification->fresh()->status === 'sent';
    }

    private function consume(User $user, string $purpose, string $otp): void
    {
        $row = AuthChallenge::where('user_id', $user->id)->where('purpose', $purpose)->lockForUpdate()->first();
        if (! $row || $row->expires_at->isPast() || ! Hash::check($otp, $row->otp_hash)) {
            $this->invalidOtp();
        }
        $row->delete();
    }

    private function invalidOtp(): never
    {
        throw ValidationException::withMessages(['otp' => 'Kode tidak valid atau sudah kedaluwarsa. Minta kode baru bila diperlukan.']);
    }
}
