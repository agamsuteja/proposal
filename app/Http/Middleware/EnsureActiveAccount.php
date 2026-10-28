<?php

namespace App\Http\Middleware;

use App\Support\Access;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $fingerprint = $request->session()->get('auth_password_fingerprint');
        if (! $user || ! $user->is_active || ! in_array($user->role, Access::ROLES, true)
            || ! is_string($fingerprint) || ! hash_equals(hash('sha256', $user->getAuthPassword()), $fingerprint)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi berakhir atau akun tidak aktif. Silakan login kembali.'], 401);
            }

            return redirect()->route('admin.login')->with('error', 'Silakan login kembali menggunakan akun aktif.');
        }

        return $next($request);
    }
}
