<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        // Support login by email or badge_id
        $field = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'badge_id';

        if (Auth::attempt([$field => $credentials['email'], 'password' => $credentials['password'], 'is_active' => true], $request->filled('remember'))) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();
            $user->update(['last_login_at' => now()]);

            AuditLog::log('LOGIN', 'AUTH', "User {$user->name} logged in successfully");

            return redirect()->intended(route('dashboard'))
                ->with('success', "Welcome back, {$user->name}!");
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records or the account is inactive.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::log('LOGOUT', 'AUTH', "User " . Auth::user()->name . " logged out");
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been successfully logged out.');
    }
}
