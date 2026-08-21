<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Validate credentials and role BEFORE touching the session.
        // Using a generic error regardless of why it failed prevents
        // leaking that valid credentials exist for a different guard.
        if (! Auth::guard('admin')->validate($credentials)) {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas.'])
                ->withInput(['email' => $request->input('email')]);
        }

        $user = Auth::guard('admin')->getProvider()->retrieveByCredentials($credentials);

        if (! $user || $user->role !== 'admin') {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas.'])
                ->withInput(['email' => $request->input('email')]);
        }

        Auth::guard('admin')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
