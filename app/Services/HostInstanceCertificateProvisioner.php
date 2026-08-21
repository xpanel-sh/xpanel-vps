<?php

namespace App\Services;

use App\Models\HostInstance;
use Throwable;

class HostInstanceCertificateProvisioner
{
    public function __construct(private ServerCommandRunner $commands) {}

    public function issue(HostInstance $instance): bool
    {
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

        if (! config('xpanel.native_hosting.apply_system_changes')) {
            $instance->update(['ssl_status' => 'staged', 'ssl_last_error' => null]);

            return false;
        }

        try {
            $email = $instance->tenant->user?->email ?: 'admin@'.$instance->panel_domain;
            $this->commands->run([
                'sudo', '-n', config('xpanel.host_instances.helper'), 'ssl-issue',
                $instance->uuid, $instance->panel_domain, $email,
            ], null, 300);
            $instance->update(['ssl_status' => 'active', 'ssl_last_error' => null]);

            return true;
        } catch (Throwable $exception) {
            $instance->update(['ssl_status' => 'error', 'ssl_last_error' => $exception->getMessage()]);
            report($exception);

            return false;
        }
    }
}
