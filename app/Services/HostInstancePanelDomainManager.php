<?php

namespace App\Services;

use App\Models\HostInstance;

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
    }

    public function apply(HostInstance $instance): void
    {
        $instance->loadMissing(['hostingAccount', 'tenant.user']);
        $this->provisioner->apply($instance);

        if ($instance->hostingAccount?->custom_panel_domain
            && $this->certificates->issue($instance->fresh(['hostingAccount', 'tenant.user']))) {
            // The first apply adds the Nginx alias. Once SSL is active, the
            // second one makes the custom URL canonical inside Host.
            $this->provisioner->apply($instance->fresh());
        }
    }
}
