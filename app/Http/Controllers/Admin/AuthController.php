<?php

// namespace App\Http\Controllers\Admin;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Departemen;
use App\Models\Grup;
use Illuminate\Validation\Rule;



class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('backend.auth.login');
    }

    // public function login(Request $request)
    // {
    //     $credentials = $request->only('email', 'password');
    //     if (Auth::attempt($credentials)) {
    //         return redirect()->route('admin.dashboard');
    //     }
    //     return back()->with('error', 'Email atau Password salah');
    // }


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {

            // CEK APAKAH AKUN SUDAH DIAKTIFKAN ADMIN
            if (Auth::user()->is_active == 0) {
                Auth::logout();

                return back()->with('error', 'Akun Anda menunggu persetujuan admin.')->withInput();
            }

            // Login normal
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        return back()->with('error', 'Email atau password salah.')->withInput();
    }


    public function logout(Request $request)
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }


    // === FORM LUPA PASSWORD ===
    public function forgotPasswordForm()
    {
        return view('backend.auth.forgot-password');
    }

    // === KIRIM KODE OTP RESET PASSWORD VIA WA ===
    public function forgotPasswordSend(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'nohp'  => 'required'
        ]);

        // Batasi permintaan OTP agar tidak disalahgunakan (maks. 3x per 10 menit per email + IP)
        $limitKey = 'forgot-otp:' . strtolower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($limitKey, 3)) {
            $menit = ceil(RateLimiter::availableIn($limitKey) / 60);
            return back()->with('error', "Terlalu banyak permintaan kode. Coba lagi dalam {$menit} menit.")->withInput();
        }

        RateLimiter::hit($limitKey, 600);

        // Cek apakah email + nohp cocok
        $user = User::where('email', $request->email)
                    ->where('nohp', $request->nohp)
                    ->first();

        if (!$user) {
            return back()->with('error', 'Email dan Nomor HP tidak cocok dengan data manapun.')->withInput();
        }

        // Generate OTP 6 digit (berlaku 10 menit), disimpan di tabel yang sama dengan OTP registrasi
        $otp = random_int(100000, 999999);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            [
                'token'          => null,
                'otp'            => $otp,
                'otp_expires_at' => now()->addMinutes(10),
                'created_at'     => now(),
            ]
        );

        // Pesan WA
        $pesan = \App\Services\WaTemplate::otpReset($user->name, $otp);

        if (!$this->sendViaFonnte($user->nohp, $pesan)) {
            DB::table('password_resets')->where('email', $user->email)->delete();

            return back()->with('error', 'Gagal mengirim kode OTP ke WhatsApp. Silakan coba lagi beberapa saat lagi.')->withInput();
        }

        return redirect()
            ->route('admin.reset.form', ['email' => $user->email])
            ->with('success', 'Kode OTP telah dikirim ke WhatsApp Anda.');
    }


    // === KIRIM WA VIA FONNTE ===
    private function sendViaFonnte($nohp, $pesan)
    {
        return app(\App\Services\FonnteService::class)->send($nohp, $pesan);
    }


    // === FORM RESET PASSWORD (INPUT OTP + PASSWORD BARU) ===
    public function resetPasswordForm(Request $request)
    {
        $email = $request->query('email');

        if (!$email) {
            return redirect()->route('admin.forgot.form')
                ->with('error', 'Silakan masukkan email dan nomor WhatsApp Anda terlebih dahulu.');
        }

        return view('backend.auth.reset-password', compact('email'));
    }

    // === PROSES RESET PASSWORD ===
    public function resetPasswordSubmit(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'otp'      => 'required|digits:6',
            'password' => 'required|min:6|confirmed',
        ]);

        // Batasi tebakan OTP (maks. 5x per 10 menit per email + IP)
        $limitKey = 'reset-otp:' . strtolower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($limitKey, 5)) {
            $menit = ceil(RateLimiter::availableIn($limitKey) / 60);
            return back()->with('error', "Terlalu banyak percobaan. Coba lagi dalam {$menit} menit.");
        }

        $reset = DB::table('password_resets')->where('email', $request->email)->first();

        if (!$reset || empty($reset->otp) || empty($reset->otp_expires_at)) {
            RateLimiter::hit($limitKey, 600);
            return back()->with('error', 'Kode OTP tidak ditemukan. Silakan minta kode baru.');
        }

        if (now()->greaterThan(\Carbon\Carbon::parse($reset->otp_expires_at))) {
            return back()->with('error', 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.');
        }

        if (!hash_equals((string) $reset->otp, (string) $request->otp)) {
            RateLimiter::hit($limitKey, 600);
            return back()->with('error', 'Kode OTP salah.');
        }

        $user = User::where('email', $reset->email)->first();

        if (!$user) {
            return back()->with('error', 'User tidak ditemukan');
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // OTP sekali pakai
        DB::table('password_resets')->where('email', $reset->email)->delete();
        RateLimiter::clear($limitKey);

        return redirect()->route('admin.login')->with('success', 'Password berhasil diubah');
    }





        /**
     * Tampilkan form registrasi
     */
    public function showRegisterForm()
    {
        // ambil daftar departemen & grup untuk dropdown
        $departemens = Departemen::orderBy('nama_departemen')->get();
        $grups = Grup::orderBy('nama_grup')->get();
        $roles = ['karyawan','departemen_head','group_head','gembala','keuangan'];

        return view('backend.auth.register', compact('departemens','grups','roles'));
    }

    /**
     * Proses registrasi dan kirim OTP via WA
     */
    public function register(Request $request)
    {
    $request->validate([
        'name' => 'required|string|max:100',
        'email' => 'required|email|unique:users,email',
        // 'nohp' => 'required|string|unique:users,nohp',
        'nohp' => 'required|string|max:15|unique:users,nohp',
        'password' => 'required|min:6|confirmed',
        'role' => ['required', Rule::in(['karyawan','departemen_head','group_head','gembala','keuangan'])],
        'departemen_id' => 'nullable|exists:departemen,id',
        'grup_id' => 'nullable|exists:grup,id',
    ]);

    // Jika role butuh departemen/grup peraturan kondisional
    if (in_array($request->role, ['departemen_head','karyawan']) && !$request->departemen_id) {
        return back()->with('error', 'Departemen wajib diisi untuk role ini')->withInput();
    }
    if ($request->role === 'group_head' && !$request->grup_id) {
        return back()->with('error', 'Grup wajib diisi untuk Group Head')->withInput();
    }

    // Buat user dengan is_active = 0 sampai verifikasi sukses
    $user = User::create([
        'departemen_id' => $request->departemen_id,
        'grup_id' => $request->grup_id,
        'name' => $request->name,
        'email' => $request->email,
        'nohp' => $request->nohp,
        'password' => Hash::make($request->password),
        'role' => $request->role,
        'is_active' => 0,
    ]);

    // Generate OTP 6 digit dan simpan di table password_resets
    $otp = rand(100000, 999999);
    $expires = now()->addMinutes(10);

    DB::table('password_resets')->updateOrInsert(
        ['email' => $user->email],
        [
            'token' => null,
            'otp' => $otp,
            'otp_expires_at' => $expires,
            'created_at' => now(),
        ]
    );

    // Kirim OTP via Fonnte (WA)
    $pesan = \App\Services\WaTemplate::otpRegistrasi($user->name, $otp);

    
    $this->sendViaFonnte($user->nohp, $pesan);


    // Redirect ke halaman verifikasi, kirim email sebagai identifier (tidak rahasiakan)
    // return redirect()->route('admin.register.verify.form', ['email' => $user->email])
    return redirect()
    ->route('admin.register.verify.form', ['email' => $user->email])
    ->with('success', 'Akun dibuat. Kode OTP telah dikirim ke WhatsApp Anda.');

    }

    /**
     * Tampilkan form verifikasi OTP (registrasi)
     */
    public function showRegisterVerifyForm(Request $request)
    {
        $email = $request->email;
        if (!$email) {
            return redirect()->route('admin.register.form')->with('error', 'Email tidak ditemukan. Silakan registrasi kembali.');
        }
        // return view('backend.auth.register-verify', compact('email'));
        return view('backend.auth.register-verify', [
            'email' => $request->email
        ]);
    }

    /**
     * Proses verifikasi OTP registrasi
     */
    public function registerVerify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $row = DB::table('password_resets')->where('email', $request->email)->first();

        if (!$row) {
            return back()->with('error', 'Tidak ada permintaan registrasi ditemukan untuk email ini.');
        }

        if (empty($row->otp) || empty($row->otp_expires_at)) {
            return back()->with('error', 'OTP tidak ditemukan. Silakan registrasi ulang.');
        }

        if (now()->greaterThan(\Carbon\Carbon::parse($row->otp_expires_at))) {
            return back()->with('error', 'OTP telah kadaluarsa. Silakan registrasi ulang.');
        }

        if ($row->otp != $request->otp) {
            return back()->with('error', 'OTP salah.');
        }

        // OTP VALID → user belum aktif sampai admin menyetujui
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        // Hapus OTP
        DB::table('password_resets')->where('email', $request->email)->delete();

        return redirect()->route('admin.login')
            ->with('success', 'OTP berhasil diverifikasi. Akun Anda sedang menunggu persetujuan admin.');
    }


    public function pendingUsers()
    {
        $users = User::where('is_active', 0)->get();
        return view('backend.admin.user-pending', compact('users'));
    }

    public function approveUser($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = 1;
        $user->save();

        return back()->with('success', 'User berhasil diaktifkan.');
    }

}
