<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    // Login-only. There is exactly one admin account for the school, seeded
    // with fixed credentials (see database/seeders/DatabaseSeeder.php) - the
    // app itself no longer offers any way to create additional admins.
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.admin_login');
    }

    // Replaces: if (isset($_POST['admin_login'])) { ... }
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Accept either the admin's username or their ROCAA#### ID in the
        // same field, so existing "username" muscle memory still works.
        $admin = \App\Models\Admin::where('username', $credentials['username'])
            ->orWhere('admin_id', $credentials['username'])
            ->first();

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            return back()->withErrors(['username' => 'Incorrect username/ID or password.'])->withInput();
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
