<?php

namespace App\Services;

use App\Models\HostInstance;
use Illuminate\Support\Facades\Log;

class HostInstanceDomainMigrator
{
    public function __construct(private HostInstanceProvisioner $provisioner) {}

    /** @return array{migrated:int,failed:int} */
    public function migrateTechnicalDomains(string $cloudDomain): array
    {
        $baseUrl = 'https://'.$cloudDomain;
        config([
            'app.url' => $baseUrl,
            'xpanel.host_instances.cloud_domain' => $cloudDomain,
            'xpanel.host_instances.control_plane_url' => $baseUrl,
            'xpanel.host_instances.broker_url' => $baseUrl.'/api/internal/host-broker',
        ]);

        $result = ['migrated' => 0, 'failed' => 0];
        HostInstance::query()->with(['tenant.user', 'hostingAccount'])->each(function (HostInstance $instance) use ($cloudDomain, &$result): void {
            $technicalPrefix = 'h-'.substr(str_replace('-', '', $instance->uuid), 0, 12).'.';
            if (! str_starts_with($instance->panel_domain, $technicalPrefix)) {
                return;
            }

            $newDomain = $technicalPrefix.$cloudDomain;
            if ($instance->panel_domain === $newDomain) {
                return;
            }

            $instance->update([
                'panel_domain' => $newDomain,
                'ssl_status' => 'waiting_dns',
                'ssl_last_error' => "Esperando SSL para {$newDomain}.",
                'ssl_attempted_at' => null,
            ]);

            try {
                $this->provisioner->apply($instance->fresh(['tenant.user', 'hostingAccount']));
                $result['migrated']++;
            } catch (\Throwable $exception) {
                $result['failed']++;
                Log::error('Host technical domain migration failed', [
                    'instance_id' => $instance->id,
                    'panel_domain' => $newDomain,
                    'exception' => $exception,
                ]);
            }
        });

        return $result;
    }
}
