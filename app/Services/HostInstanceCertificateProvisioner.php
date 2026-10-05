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
        $customDomain = $instance->hostingAccount?->custom_panel_domain;
        // A customer-selected address is independent of the original address.
        // Never include the original domain in its ACME request.
        $certificateDomain = $customDomain ?: $instance->panel_domain;
        $resolvedIps = collect($this->addresses($certificateDomain));
        if ($serverIp === '' || ! $resolvedIps->contains($serverIp)) {
            $instance->update([
                'ssl_status' => 'waiting_dns',
                'ssl_last_error' => "Crea un registro A para {$certificateDomain} apuntando a ".($serverIp ?: 'la IP del servidor').'.',
            ]);
            if ($customDomain) {
                $instance->hostingAccount->update([
                    'custom_domain_status' => 'waiting_dns',
                    'custom_domain_last_error' => $instance->ssl_last_error,
                ]);
            }

            return false;
        }

        $customReady = (bool) $customDomain;
        if ($customDomain) {
            $instance->hostingAccount->update([
                'custom_domain_status' => 'issuing',
                'custom_domain_last_error' => null,
            ]);
        }

        if (! config('xpanel.native_hosting.apply_system_changes')) {
            $instance->update(['ssl_status' => 'staged', 'ssl_last_error' => null]);

            return false;
        }

        try {
            $email = $instance->hostingAccount?->admin_email
                ?: ($instance->tenant->user?->email ?: 'admin@'.$certificateDomain);
            $arguments = [
                'sudo', '-n', config('xpanel.host_instances.helper'), 'ssl-issue',
                $instance->uuid, $certificateDomain, $email,
            ];
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

    protected function addresses(string $domain): array
    {
        return gethostbynamel($domain) ?: [];
    }
}
