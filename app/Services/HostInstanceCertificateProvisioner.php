<?php

namespace App\Services;

use App\Models\HostInstance;
use Throwable;

class HostInstanceCertificateProvisioner
{
    public function __construct(private ServerCommandRunner $commands) {}

    public function issue(HostInstance $instance): bool
    {
        $instance->loadMissing(['tenant.user', 'hostingAccount']);
        $instance->update(['ssl_attempted_at' => now()]);

        if ($instance->status !== 'active') {
            $instance->update(['ssl_status' => 'pending', 'ssl_last_error' => 'La instancia aún no está activa.']);

            return false;
        }

        $serverIp = trim((string) config('xpanel.server_ip'));
        $resolvedIps = collect(gethostbynamel($instance->panel_domain) ?: []);
        if ($serverIp === '' || ! $resolvedIps->contains($serverIp)) {
            $instance->update([
                'ssl_status' => 'waiting_dns',
                'ssl_last_error' => "Crea un registro A para {$instance->panel_domain} apuntando a ".($serverIp ?: 'la IP del servidor').'.',
            ]);

            return false;
        }

        $customDomain = $instance->hostingAccount?->custom_panel_domain;
        $customReady = false;
        if ($customDomain) {
            $customReady = collect(gethostbynamel($customDomain) ?: [])->contains($serverIp);
            $instance->hostingAccount->update([
                'custom_domain_status' => $customReady ? 'issuing' : 'waiting_dns',
                'custom_domain_last_error' => $customReady ? null : "Crea un registro A o CNAME para {$customDomain} apuntando a {$serverIp}.",
            ]);
        }

        if (! config('xpanel.native_hosting.apply_system_changes')) {
            $instance->update(['ssl_status' => 'staged', 'ssl_last_error' => null]);

            return false;
        }

        try {
            $email = $instance->hostingAccount?->admin_email
                ?: ($instance->tenant->user?->email ?: 'admin@'.$instance->panel_domain);
            $arguments = [
                'sudo', '-n', config('xpanel.host_instances.helper'), 'ssl-issue',
                $instance->uuid, $instance->panel_domain, $email,
            ];
            if ($customReady) {
                $arguments[] = $customDomain;
            }
            $this->commands->run($arguments, null, 300);
            $instance->update(['ssl_status' => 'active', 'ssl_last_error' => null]);
            if ($customReady) {
                $instance->hostingAccount->update(['custom_domain_status' => 'active', 'custom_domain_last_error' => null]);
            }

            return true;
        } catch (Throwable $exception) {
            $instance->update(['ssl_status' => 'error', 'ssl_last_error' => $exception->getMessage()]);
            if ($customReady) {
                $instance->hostingAccount?->update(['custom_domain_status' => 'error', 'custom_domain_last_error' => $exception->getMessage()]);
            }
            report($exception);

            return false;
        }
    }
}
