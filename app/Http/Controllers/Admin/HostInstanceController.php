<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostInstance;
use App\Models\Tenant;
use App\Services\HostInstanceProvisioner;
use App\Services\HostInstanceCertificateProvisioner;
use Illuminate\Http\Request;

class HostInstanceController extends Controller
{
    public function index()
    {
        $instances = HostInstance::with(['tenant.user', 'tenant.plan'])->latest()->paginate(20);

        return view('admin.instances.index', compact('instances'));
    }

    public function store(Request $request, Tenant $tenant, HostInstanceProvisioner $provisioner)
    {
        abort_unless(config('xpanel.host_instances.enabled'), 404);
        abort_if($tenant->hostInstance()->exists(), 409, 'Este cliente ya tiene una instancia de XPanel Host.');

        $validated = $request->validate([
            'panel_domain' => [
                'required', 'string', 'max:253', 'unique:host_instances,panel_domain',
                'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/',
            ],
            'owner_password' => config('xpanel.native_hosting.apply_system_changes')
                ? ['required', 'string', 'min:16', 'max:128']
                : ['nullable', 'string', 'min:16', 'max:128'],
        ]);

        $instance = $provisioner->create(
            $tenant,
            strtolower($validated['panel_domain']),
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
