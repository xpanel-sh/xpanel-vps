<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostInstance;
use App\Models\HostingAccount;
use App\Models\Tenant;
use App\Services\HostInstanceProvisioner;
use App\Services\HostInstanceCertificateProvisioner;
use Illuminate\Http\Request;

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
        $validated = $request->validate([
            'panel_domain' => [
                'nullable', 'string', 'max:253', 'unique:host_instances,panel_domain',
                'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/',
            ],
            'owner_password' => config('xpanel.native_hosting.apply_system_changes')
                ? ['required', 'string', 'min:16', 'max:128']
                : ['nullable', 'string', 'min:16', 'max:128'],
            'plan_id' => ['nullable', 'integer', 'exists:hosting_plans,id'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $account = HostingAccount::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenant->id,
            'hosting_plan_id' => $validated['plan_id'] ?? $tenant->plan_id,
            'name' => $validated['name'] ?? 'Hosting '.($tenant->hostingAccounts()->count() + 1),
            'status' => 'active',
        ]);

        $instance = $provisioner->create(
            $account,
            filled($validated['panel_domain'] ?? null) ? strtolower($validated['panel_domain']) : null,
            $validated['owner_password'] ?? null,
        );

        $message = $instance->status === 'active'
            ? 'Instancia instalada y activa. El dueño ya puede entrar a XPanel Host.'
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
}
