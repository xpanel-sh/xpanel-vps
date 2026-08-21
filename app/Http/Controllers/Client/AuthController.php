<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('client.dashboard');
        }

        return view('client.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'plan' => ['nullable', 'string', 'exists:hosting_plans,slug'],
        ]);

        unset($credentials['plan']);

        // Validate credentials and role BEFORE touching the session.
        // Generic error prevents leaking that valid credentials exist for admin.
        if (! Auth::guard('web')->validate($credentials)) {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas.'])
                ->withInput(['email' => $request->input('email')]);
        }

        $user = Auth::guard('web')->getProvider()->retrieveByCredentials($credentials);

        if (! $user || $user->role !== 'client') {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas.'])
                ->withInput(['email' => $request->input('email')]);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $destination = $request->filled('plan')
            ? route('client.plans.index', ['selected' => (string) $request->input('plan')])
            : route('client.dashboard');

        return redirect()->intended($destination);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login');
    }
}
