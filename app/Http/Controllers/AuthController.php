<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'active' => true], $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->with('error', 'Wrong email or password.');
        }

        $request->session()->regenerate();
        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        LoginLog::create(['user_id' => $user->id, 'ip' => $request->ip()]);

        return redirect()->intended($user->isStaff() ? route('admin.dashboard') : route('catalog'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    /** Self-registration creates a "reader" (portal) account - can read e-books, no back-office access. */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($data + ['role' => 'reader']);
        Auth::login($user);
        $request->session()->regenerate();
        LoginLog::create(['user_id' => $user->id, 'ip' => $request->ip()]);

        return redirect()->route('catalog');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
