<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostInstance;
use App\Models\HostingAccount;
use App\Models\Tenant;
use App\Services\HostInstanceProvisioner;
use App\Services\HostInstanceCertificateProvisioner;
use App\Services\HostSsoLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HostInstanceController extends Controller
{
    public function index()
    {
        $instances = HostInstance::with(['tenant.user', 'hostingAccount.plan'])->latest()->paginate(20);

        return view('admin.instances.index', compact('instances'));
    }

    public function store(Request $request, Tenant $tenant, HostInstanceProvisioner $provisioner)
    {
        abort_unless(config('xpanel.host_instances.enabled'), 404);
        $tenant->loadMissing('user');
        $emailRules = ['required', 'email', 'max:255'];
        if (! $tenant->access_ready) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($tenant->user_id);
        }
        $validated = $request->validate([
            'panel_domain' => [
                'nullable', 'string', 'max:253', 'unique:host_instances,panel_domain',
                'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/',
            ],
            'plan_id' => ['required', 'integer', 'exists:hosting_plans,id'],
            'name' => ['nullable', 'string', 'max:120'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => $emailRules,
            'admin_password' => ['required', 'string', 'min:16', 'max:128'],
        ]);

        $account = DB::transaction(function () use ($tenant, $validated): HostingAccount {
            if (! $tenant->access_ready) {
                $tenant->user->update([
                    'name' => $validated['admin_name'],
                    'email' => strtolower($validated['admin_email']),
                    'password' => Hash::make($validated['admin_password']),
                    'role' => 'client',
                ]);
                $tenant->update(['access_ready' => true]);
            }

            return HostingAccount::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'hosting_plan_id' => $validated['plan_id'],
                'name' => $validated['name'] ?? 'Hosting '.($tenant->hostingAccounts()->count() + 1),
                'admin_name' => $validated['admin_name'],
                'admin_email' => strtolower($validated['admin_email']),
                'status' => 'active',
            ]);
        });

        try {
            $instance = $provisioner->create(
                $account,
                filled($validated['panel_domain'] ?? null) ? strtolower($validated['panel_domain']) : null,
                $validated['admin_password'],
            );
        } catch (\Throwable $e) {
            Log::error('Additional hosting provisioning failed', [
                'tenant_id' => $tenant->id,
                'hosting_account_id' => $account->id,
                'exception' => $e,
            ]);

            return redirect()->route('admin.clients.show', $tenant)->withErrors([
                'hosting' => 'La cuenta se creó, pero la instancia no terminó de aprovisionarse. Usa Aplicar para reintentarlo.',
            ]);
        }

        $message = $instance->status === 'active'
            ? 'Instancia instalada y activa con su propia cuenta administradora.'
            : 'Configuración generada. Activa XPANEL_APPLY_SYSTEM_CHANGES para aplicarla al servidor.';

        return redirect()->route('admin.clients.show', $tenant)->with('success', $message);
    }

    public function apply(Request $request, HostInstance $instance, HostInstanceProvisioner $provisioner)
    {
        $validated = $request->validate([
            'owner_password' => ['nullable', 'string', 'min:16', 'max:128'],
        ]);
        $provisioner->apply($instance, $validated['owner_password'] ?? null);

        return back()->with('success', 'La configuración de la instancia fue aplicada.');
    }

    public function retrySsl(HostInstance $instance, HostInstanceCertificateProvisioner $certificates)
    {
        $issued = $certificates->issue($instance->load('tenant.user'));

        return back()->with(
            $issued ? 'success' : 'warning',
            $issued ? 'Certificado SSL emitido correctamente.' : ($instance->fresh()->ssl_last_error ?: 'SSL pendiente de DNS.'),
        );
    }

    public function access(HostInstance $instance, HostSsoLink $sso)
    {
        $instance->loadMissing(['hostingAccount', 'tenant.user']);
        abort_unless($instance->status === 'active' && ($instance->hostingAccount?->admin_email || $instance->tenant?->user), 409, 'La instancia todavía no está disponible.');

        return redirect()->away($sso->for($instance));
    }
}
