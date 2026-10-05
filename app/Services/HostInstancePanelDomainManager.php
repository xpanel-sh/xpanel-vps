<?php

namespace App\Services;

use App\Models\HostInstance;
use RuntimeException;

class HostInstancePanelDomainManager
{
    public function __construct(
        private readonly HostInstanceProvisioner $provisioner,
        private readonly HostInstanceCertificateProvisioner $certificates,
    ) {}

    public function stage(HostInstance $instance, ?string $domain): void
    {
        $account = $instance->hostingAccount()->firstOrFail();
        $account->update([
            'custom_panel_domain' => $domain,
            'custom_domain_status' => $domain ? 'waiting_dns' : 'not_configured',
            'custom_domain_last_error' => null,
        ]);
        $instance->update(['ssl_status' => 'pending', 'ssl_last_error' => null]);
    }

    public function apply(HostInstance $instance): void
    {
        $instance->loadMissing(['hostingAccount', 'tenant.user']);
        // Nginx must learn the alias before ACME runs, but restarting the FPM
        // process here would terminate the Host request that initiated it.
        $this->provisioner->apply($instance, null, 'skip');

        if ($instance->hostingAccount?->custom_panel_domain) {
            $issued = $this->certificates->issue($instance->fresh(['hostingAccount', 'tenant.user']));
            if (! $issued) {
                $fresh = $instance->fresh(['hostingAccount']);
                throw new RuntimeException($fresh->hostingAccount?->custom_domain_last_error
                    ?: $fresh->ssl_last_error
                    ?: 'Nginx aceptó el dominio, pero el certificado SSL todavía no pudo emitirse.');
            }

            // The first apply adds the Nginx alias. Once SSL is active, the
            // second one makes the custom URL canonical inside Host.
            $this->provisioner->apply($instance->fresh(), null, 'deferred');
        }
    }
}
