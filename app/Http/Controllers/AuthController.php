<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->isOwner() ? 'owner.report' : 'admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'role' => ['required', 'in:admin,owner'],
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'role.required' => 'Pilih peran terlebih dahulu.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()
                ->withInput($r->only('email', 'role'))
                ->withErrors(['email' => 'Email atau kata sandi salah.']);
        }

        if (! $user->is_active) {
            return back()
                ->withInput($r->only('email', 'role'))
                ->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi pemilik usaha.']);
        }

        if ($user->role !== $data['role']) {
            return back()
                ->withInput($r->only('email', 'role'))
                ->withErrors(['email' => 'Peran yang dipilih tidak sesuai dengan akun ini.']);
        }

        Auth::login($user);
        $r->session()->regenerate();

        $target = $user->isOwner() ? route('owner.report') : route('admin.dashboard');

        return redirect()->intended($target);
    }

    public function logout(Request $r): RedirectResponse
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('status', 'Anda berhasil keluar.');
    }
}
