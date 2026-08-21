<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\EmailAccount;
use App\Services\DaemonClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EmailAccountController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $accounts = EmailAccount::query()
            ->with('domain')
            ->where('tenant_id', $tenant->id)
            ->when($request->search, fn ($q, $s) => $q->where('email', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15);

        return view('client.mail.index', compact('accounts', 'tenant'));
    }

    public function create(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $domains = Domain::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('domain')
            ->get(['id', 'domain']);

        return view('client.mail.create', compact('domains', 'tenant'));
    }

    public function store(Request $request, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');
        $plan = $tenant->plan;

        if ($plan && $plan->email_accounts > 0) {
            $currentAccounts = EmailAccount::where('tenant_id', $tenant->id)->count();
            if ($currentAccounts >= $plan->email_accounts) {
                return back()->withErrors([
                    'local_part' => 'Email account limit reached for the assigned plan.',
                ])->withInput();
            }
        }

        $validated = $request->validate([
            'domain_id' => ['required', 'integer', 'exists:domains,id'],
            'local_part' => ['required', 'alpha_dash', 'min:1', 'max:64'],
            'password' => ['required', 'string', 'min:12', 'max:128'],
            'quota_mb' => ['required', 'integer', 'min:128', 'max:102400'],
        ]);

        $domain = Domain::where('id', $validated['domain_id'])
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $email = strtolower($validated['local_part'].'@'.$domain->domain);
        if (EmailAccount::where('email', $email)->exists()) {
            return back()->withErrors(['local_part' => 'This email account already exists.'])->withInput();
        }

        $account = EmailAccount::create([
            'tenant_id' => $tenant->id,
            'domain_id' => $domain->id,
            'local_part' => strtolower($validated['local_part']),
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'quota_mb' => $validated['quota_mb'],
            'status' => 'provisioning',
            'last_password_change_at' => now(),
        ]);

        try {
            $daemon->createEmailAccount($account->email, $domain->domain, $account->quota_mb, $validated['password']);
            $account->update(['status' => 'active']);
        } catch (\Throwable $e) {
            Log::warning('Mail account provisioning failed', ['email_account_id' => $account->id, 'exception' => $e]);
            $account->update(['status' => 'provision_error']);

            return redirect()->route('client.mail.index')
                ->withErrors(['email' => 'Correo registrado, pero el agente no pudo provisionarlo. Revisa operaciones del agente o contacta soporte.']);
        }

        return redirect()->route('client.mail.index')
            ->with('success', 'Correo creado correctamente. XPanel no muestra ni recupera la contraseña guardada.');
    }

    public function resetPassword(Request $request, EmailAccount $emailAccount, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');
        if ($emailAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:12', 'max:128'],
        ]);

        try {
            $daemon->resetEmailPassword($emailAccount->email, $validated['password']);
        } catch (\Throwable $e) {
            Log::warning('Mail password reset failed', ['email_account_id' => $emailAccount->id, 'exception' => $e]);

            return redirect()->route('client.mail.index')
                ->withErrors(['email' => 'El agente no pudo aplicar la nueva clave. Revisa operaciones del agente o contacta soporte.']);
        }

        $emailAccount->update([
            'password' => Hash::make($validated['password']),
            'last_password_change_at' => now(),
        ]);

        return redirect()->route('client.mail.index')
            ->with('success', "Contraseña actualizada para {$emailAccount->email}. XPanel no la mostrará nuevamente.");
    }

    public function destroy(Request $request, EmailAccount $emailAccount, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');
        if ($emailAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        try {
            $daemon->deleteEmailAccount($emailAccount->email);
        } catch (\Throwable $e) {
            Log::warning('Mail account deletion failed', ['email_account_id' => $emailAccount->id, 'exception' => $e]);
            $emailAccount->update(['status' => 'delete_error']);

            return redirect()->route('client.mail.index')
                ->withErrors(['email' => 'El agente no pudo eliminar el correo. Revisa operaciones del agente o contacta soporte.']);
        }

        $emailAccount->delete();

        return redirect()->route('client.mail.index')->with('success', 'Correo eliminado correctamente.');
    }
}
