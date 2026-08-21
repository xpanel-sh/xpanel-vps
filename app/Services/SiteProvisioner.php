<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\ServerNode;
use App\Models\Site;
use App\Models\Tenant;
use RuntimeException;

class SiteProvisioner
{
    public const NATIVE_TYPES = ['php', 'static'];

    public function __construct(
        private NativeSiteProvisioner $native,
        private DaemonClient $daemon,
        private NativePackageManager $packages,
    ) {}

    public static function isNativeType(string $projectType): bool
    {
        return in_array($projectType, self::NATIVE_TYPES, true);
    }

    /** @return array<int, string> */
    public function availableEngines(): array
    {
        $allowed = $this->packages->clientWebServers();

        return $allowed !== [] ? $allowed : ['nginx'];
    }

    /** @return array<int, string> */
    public function availablePhpVersions(): array
    {
        $allowed = $this->packages->clientPhpVersions();

        return $allowed !== [] ? $allowed : ['8.2'];
    }

    public function provisionForTenant(Tenant $tenant, array $data): Site
    {
        if (! config('xpanel.native_hosting.enabled', true)) {
            throw new RuntimeException('El alojamiento nativo está deshabilitado.');
        }

        $plan = $tenant->plan;
        if ($plan && $plan->max_sites > 0 && $tenant->sites()->count() >= $plan->max_sites) {
            throw new RuntimeException('Se alcanzó el límite de sitios del plan contratado.');
        }

        $projectType = (string) $data['project_type'];
        if (! self::isNativeType($projectType)) {
            throw new RuntimeException('Los sitios web solo admiten PHP o contenido estático. Usa el módulo Docker para otras aplicaciones.');
        }

        $webServer = (string) ($data['web_server'] ?? 'nginx');
        $phpVersion = (string) ($data['php_version'] ?? $this->availablePhpVersions()[0]);

        if (! in_array($webServer, $this->availableEngines(), true)) {
            throw new RuntimeException('El motor web no está habilitado por el administrador.');
        }

        if (! in_array($phpVersion, $this->availablePhpVersions(), true)) {
            throw new RuntimeException('La versión PHP no está habilitada por el administrador.');
        }

        $node = ServerNode::query()->where('is_active', true)->first();
        if (! $node) {
            throw new RuntimeException('No existe un servidor activo para provisionar el sitio.');
        }

        $site = Site::create([
            'tenant_id' => $tenant->id,
            'server_node_id' => $node->id,
            'domain' => strtolower($data['domain']),
            'project_type' => $projectType,
            'web_server' => $webServer,
            'php_version' => $phpVersion,
            'provisioning_driver' => 'native',
            'status' => 'provisioning',
        ]);

        try {
            $site->setRelation('tenant', $tenant);
            $this->native->provision($site);
            Domain::firstOrCreate(
                ['domain' => $site->domain],
                [
                    'tenant_id' => $tenant->id,
                    'site_id' => $site->id,
                    'type' => 'primary',
                    'dns_status' => 'pending',
                    'ssl_status' => 'pending',
                    'is_active' => true,
                ]
            );

            return $site->refresh();
        } catch (\Throwable $e) {
            $site->delete();
            throw new RuntimeException('No se pudo provisionar el sitio nativo.', previous: $e);
        }
    }

    /** @param array<string, mixed> $attributes */
    public function reconfigure(Site $site, array $attributes): void
    {
        if (! $site->isNative()) {
            throw new RuntimeException('El sitio no utiliza el motor nativo.');
        }

        $original = $site->getAttributes();
        $site->fill($attributes);

        try {
            $this->native->reconfigure($site);
        } catch (\Throwable $e) {
            $site->setRawAttributes($original, true);
            throw $e;
        }
    }

    public function restart(Site $site): void
    {
        if ($site->isNative()) {
            $this->native->restart($site);

            return;
        }

        if (! config('xpanel.docker.enabled', false)) {
            throw new RuntimeException('El módulo Docker está deshabilitado.');
        }

        $this->daemon->restartSite($this->containerName($site));
    }

    public function remove(Site $site): void
    {
        if ($site->isNative()) {
            $this->native->remove($site);

            return;
        }

        if (! config('xpanel.docker.enabled', false)) {
            throw new RuntimeException('El módulo Docker está deshabilitado.');
        }

        $this->daemon->deleteSite($this->containerName($site));
    }

    private function containerName(Site $site): string
    {
        return 'xpanel-site-'.str_replace('.', '-', strtolower($site->domain));
    }
}
