<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user()->role);
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $role = Auth::user()->role;
            return $this->redirectBasedOnRole($role);
        }

        return back()->withErrors([
            'username' => 'Username atau password yang Anda masukkan salah.',
        ])->onlyInput('username');
    }

    public function quickLogin(string $role)
    {
        $validRoles = ['ukasir', 'ugudang', 'uadmin'];
        if (!in_array($role, $validRoles)) {
            return redirect()->route('login')->with('error', 'Role tidak valid.');
        }

        $user = User::where('role', $role)->first();
        if ($user) {
            Auth::login($user);
            request()->session()->regenerate();
            return $this->redirectBasedOnRole($role)->with('success', 'Berhasil login sebagai ' . $user->name . ' (' . strtoupper($role) . ')');
        }

        return redirect()->route('login')->with('error', 'Pengguna demo tidak ditemukan.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    private function redirectBasedOnRole(string $role)
    {
        return match ($role) {
            'ukasir' => redirect()->route('pos.index'),
            'ugudang' => redirect()->route('gudang.index'),
            'uadmin' => redirect()->route('admin.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
