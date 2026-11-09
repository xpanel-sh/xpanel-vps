<?php

namespace App\Services;

use App\Models\HostBrokerResource;
use App\Models\HostInstance;
use App\Models\HostingAccount;
use Illuminate\Validation\ValidationException;
use Throwable;

class InitialHostSiteProvisioner
{
    public function __construct(private readonly ServerCommandRunner $commands) {}

    public function validateAvailable(?string $domain): ?string
    {
        $domain = strtolower(rtrim(trim((string) $domain), '.'));
        if ($domain === '') {
            return null;
        }
        if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $domain)) {
            throw ValidationException::withMessages(['site_domain' => 'Introduce un dominio válido para el primer sitio.']);
        }
        $cloudDomain = strtolower(trim((string) config('xpanel.host_instances.cloud_domain'), '.'));
        if ($domain === $cloudDomain) {
            throw ValidationException::withMessages(['site_domain' => 'El dominio principal de Cloud está reservado para el panel.']);
        }
        if (HostInstance::query()->where('panel_domain', $domain)->exists()
            || HostingAccount::query()->where('custom_panel_domain', $domain)->orWhere('initial_site_domain', $domain)->exists()
            || HostBrokerResource::query()->where('type', 'site-domain')->where('name', $domain)->exists()) {
            throw ValidationException::withMessages(['site_domain' => 'Este dominio ya pertenece a otro panel o sitio.']);
        }
        return $domain;
    }

    public function provision(HostingAccount $account): bool
    {
        $instance = $account->hostInstance;
        if (! $account->initial_site_domain || $account->initial_site_status !== 'pending' || $instance?->status !== 'active') {
            return false;
        }
        if (! config('xpanel.native_hosting.apply_system_changes')) {
            return false;
        }
        try {
            $this->commands->run([
                'sudo', '-n', config('xpanel.host_instances.helper'), 'initial-site-create',
                $instance->uuid, $account->initial_site_domain, $instance->release_path,
            ], null, 1800);
            $account->update(['initial_site_status' => 'active', 'initial_site_error' => null]);
            return true;
        } catch (Throwable $exception) {
            $account->update(['initial_site_status' => 'error', 'initial_site_error' => $exception->getMessage()]);
            report($exception);
            return false;
        }
    }
}
