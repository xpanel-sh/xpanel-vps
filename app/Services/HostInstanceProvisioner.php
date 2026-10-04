<?php

namespace App\Services;

use App\Models\HostInstance;
use App\Models\HostingAccount;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Throwable;

class HostInstanceProvisioner
{
    public function __construct(
        private HostInstanceConfigGenerator $generator,
        private ServerCommandRunner $commands,
        private HostInstanceResourceLimiter $limits,
    ) {}

    public function create(HostingAccount|Tenant $account, ?string $panelDomain = null, ?string $password = null): HostInstance
    {
        if ($account instanceof Tenant) {
            $tenant = $account;
            $account = $tenant->hostingAccounts()->firstOrCreate(
                ['name' => 'Hosting principal'],
                [
                    'uuid' => (string) Str::uuid(),
                    'hosting_plan_id' => $tenant->plan_id,
                    'status' => 'active',
                ],
            );
        } else {
            $account->loadMissing('tenant');
            $tenant = $account->tenant;
        }

        if ($account->hostInstance()->exists()) {
            throw new \LogicException('Esta cuenta de hosting ya tiene una instancia XPanel Host.');
        }

        $uuid = (string) Str::uuid();
        $panelDomain ??= $this->technicalPanelDomain($uuid);
        $root = rtrim(config('xpanel.host_instances.root'), '/').'/'.$uuid;
        $configuredRelease = config('xpanel.host_instances.release_path');
        $releasePath = realpath($configuredRelease) ?: $configuredRelease;

        $instance = $account->hostInstance()->create([
            'tenant_id' => $tenant->id,
            'uuid' => $uuid,
            'panel_domain' => strtolower($panelDomain),
            'access_port' => $this->nextAccessPort(),
            'system_user' => 'xhi'.substr(str_replace('-', '', $uuid), 0, 12),
            // Resolve /opt/xpanel-host/current now so this client remains pinned
            // to an immutable release until an explicit per-instance update.
            'release_path' => str_replace('\\', '/', $releasePath),
            'instance_root' => $root,
            'database_path' => $root.'/database/database.sqlite',
            'broker_secret' => bin2hex(random_bytes(32)),
            'initial_password' => $password,
            'php_version' => config('xpanel.host_instances.php_version'),
            'version' => config('xpanel.host_instances.version'),
            'status' => 'pending',
        ]);

        return $this->apply($instance, $password);
    }

    private function technicalPanelDomain(string $uuid): string
    {
        $cloudDomain = strtolower(trim((string) config('xpanel.host_instances.cloud_domain'), '.'));
        if (! filter_var($cloudDomain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new \RuntimeException('XPANEL_CLOUD_DOMAIN no contiene un dominio válido.');
        }

        return 'h-'.substr(str_replace('-', '', $uuid), 0, 12).'.'.$cloudDomain;
    }

    public function apply(HostInstance $instance, ?string $password = null): HostInstance
    {
        try {
            if (blank($instance->broker_secret)) {
                $instance->forceFill(['broker_secret' => bin2hex(random_bytes(32))])->save();
            }
            $files = $this->generator->generate($instance);

            if (! config('xpanel.native_hosting.apply_system_changes')) {
                $instance->update([
                    'status' => $instance->provisioned_at ? $instance->status : 'staged',
                    'last_error' => null,
                ]);

                return $instance->fresh();
            }

            $instance->loadMissing(['tenant.user', 'hostingAccount']);
            $owner = $instance->tenant->user;
            $ownerName = $instance->hostingAccount?->admin_name ?: ($owner?->name ?? 'Administrador');
            $ownerEmail = $instance->hostingAccount?->admin_email ?: ($owner?->email ?? 'admin@'.$instance->panel_domain);
            $this->commands->run([
                'sudo', config('xpanel.host_instances.helper'), 'apply', $instance->uuid,
                $instance->system_user, $instance->panel_domain, $instance->php_version,
                $instance->release_path, $files['directory'], $ownerName,
                $ownerEmail,
                ...$this->limits->helperArguments($instance),
            ], ($password ?? '')."\n", 600);

            $instance->update([
                'status' => 'active',
                'last_error' => null,
                'provisioned_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $instance->update(['status' => 'error', 'last_error' => $exception->getMessage()]);
            throw $exception;
        }

        return $instance->fresh();
    }

    private function nextAccessPort(): int
    {
        return Cache::lock('xpanel-host-instance-port', 10)->block(5, function (): int {
            $start = (int) config('xpanel.host_instances.fallback_port_start');
            $end = (int) config('xpanel.host_instances.fallback_port_end');

            if ($start < 1024 || $end > 65535 || $start > $end) {
                throw new \RuntimeException('El rango de puertos temporales de Host no es válido.');
            }

            $used = HostInstance::query()->whereNotNull('access_port')->pluck('access_port')->flip();
            for ($port = $start; $port <= $end; $port++) {
                if (! $used->has($port)) {
                    return $port;
                }
            }

            throw new \RuntimeException('No quedan puertos temporales disponibles para instancias Host.');
        });
    }

    public function setSuspended(HostInstance $instance, bool $suspended): HostInstance
    {
        $status = $suspended ? 'suspended' : ($instance->provisioned_at ? 'active' : 'staged');

        if ($instance->provisioned_at && config('xpanel.native_hosting.apply_system_changes')) {
            $this->commands->run([
                'sudo', config('xpanel.host_instances.helper'), 'set-status', $instance->uuid, $status,
            ]);
        }

        $instance->update(['status' => $status]);

        return $instance->fresh();
    }
}
