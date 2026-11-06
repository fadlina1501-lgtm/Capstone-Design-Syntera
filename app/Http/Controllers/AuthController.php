<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['username' => 'Username atau password tidak sesuai.']);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (strtolower((string) $user->status_account) !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages(['username' => 'Akun tidak aktif. Hubungi administrator.']);
        }

        $user->last_login = now();
        $user->save();
        AuditLogger::log($user->user_id, 'Login ke sistem', $request);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        AuditLogger::log($request->user()?->user_id, 'Logout dari sistem', $request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda berhasil logout.');
    }
}
