<?php

namespace App\Services;

use App\Models\DockerInstance;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantLifecycleManager
{
    public function __construct(
        private NativeSiteProvisioner $sites,
        private DaemonClient $daemon,
        private HostInstanceProvisioner $hostInstances,
    ) {}

    public function setSuspended(Tenant $tenant, bool $suspended): void
    {
        $status = $suspended ? 'suspended' : 'active';

        DB::transaction(function () use ($tenant, $status): void {
            $tenant->update(['status' => $status]);
            $tenant->sites()->where('provisioning_driver', 'native')->update(['status' => $status]);
        });

        $tenant->sites()->where('provisioning_driver', 'native')->get()->each(
            fn ($site) => $this->sites->reconfigure($site)
        );

        if ($instance = $tenant->hostInstance) {
            $this->hostInstances->setSuspended($instance, $suspended);
        }

        if ($suspended && config('xpanel.docker.enabled', false)) {
            DockerInstance::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', ['running', 'active'])
                ->get()
                ->each(function (DockerInstance $instance) use ($tenant): void {
                    $this->daemon->dockerAppStop($tenant->code, $instance->slug);
                    $instance->update(['status' => 'stopped']);
                });
        }
    }
}
