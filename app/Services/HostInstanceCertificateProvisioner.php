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
        $certificateDomain = $instance->panel_domain;
        $resolvedIps = collect($this->addresses($certificateDomain));
        if ($serverIp === '' || ! $resolvedIps->contains($serverIp)) {
            $instance->update([
                'ssl_status' => 'waiting_dns',
                'ssl_last_error' => "Crea un registro A para {$certificateDomain} apuntando a ".($serverIp ?: 'la IP del servidor').'.',
            ]);

            return false;
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
            $customDomain = $instance->hostingAccount?->custom_panel_domain;
            $includeCustom = $customDomain
                && $instance->hostingAccount->custom_domain_status === 'active'
                && in_array($serverIp, $this->addresses($customDomain), true);
            if ($includeCustom) {
                $arguments[] = $customDomain;
            }
            $this->commands->run($arguments, null, 300);
            $instance->update(['ssl_status' => 'active', 'ssl_last_error' => null]);
            if ($customDomain && ! $includeCustom && $instance->hostingAccount->custom_domain_status === 'active') {
                $instance->hostingAccount->update(['custom_domain_status' => 'waiting_dns']);
            }

            return true;
        } catch (Throwable $exception) {
            $instance->update(['ssl_status' => 'error', 'ssl_last_error' => $exception->getMessage()]);
            report($exception);

            return false;
        }
    }

    public function issueCustom(HostInstance $instance): bool
    {
        $instance->loadMissing(['tenant.user', 'hostingAccount']);
        $account = $instance->hostingAccount;
        $domain = $account?->custom_panel_domain;
        if (! $domain || $instance->status !== 'active') {
            return false;
        }
        $instance->update(['ssl_attempted_at' => now()]);
        $serverIp = trim((string) config('xpanel.server_ip'));
        if ($serverIp === '' || ! in_array($serverIp, $this->addresses($domain), true)) {
            $account->update([
                'custom_domain_status' => 'waiting_dns',
                'custom_domain_last_error' => "Crea un registro A para {$domain} apuntando a ".($serverIp ?: 'la IP del servidor').'.',
            ]);
            return false;
        }
        // The shared certificate must contain both names. A failed extension
        // leaves the already issued technical certificate in place.
        if ($instance->ssl_status !== 'active' && ! $this->issue($instance)) {
            $account->update(['custom_domain_status' => 'waiting_dns', 'custom_domain_last_error' => $instance->fresh()->ssl_last_error]);
            return false;
        }
        if (! config('xpanel.native_hosting.apply_system_changes')) {
            $account->update(['custom_domain_status' => 'staged']);
            return false;
        }
        $account->update(['custom_domain_status' => 'issuing', 'custom_domain_last_error' => null]);
        try {
            $email = $account->admin_email ?: ($instance->tenant->user?->email ?: 'admin@'.$domain);
            $this->commands->run([
                'sudo', '-n', config('xpanel.host_instances.helper'), 'ssl-issue',
                $instance->uuid, $instance->panel_domain, $email, $domain,
            ], null, 300);
            $account->update(['custom_domain_status' => 'active', 'custom_domain_last_error' => null]);
            return true;
        } catch (Throwable $exception) {
            $account->update(['custom_domain_status' => 'error', 'custom_domain_last_error' => $exception->getMessage()]);
            report($exception);
            return false;
        }
    }

    protected function addresses(string $domain): array
    {
        return gethostbynamel($domain) ?: [];
    }
}
