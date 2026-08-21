<?php

namespace App\Services;

use App\Models\ServerNode;
use App\Models\SoftwarePackage;
use RuntimeException;

class NativePackageManager
{
    /** @return array<int, array<string, mixed>> */
    public function catalog(): array
    {
        $packages = [
            ['slug' => 'nginx', 'label' => 'Nginx', 'category' => 'webserver', 'version' => null, 'default' => true],
            ['slug' => 'apache', 'label' => 'Apache', 'category' => 'webserver', 'version' => null, 'default' => false],
            ['slug' => 'php81', 'label' => 'PHP 8.1', 'category' => 'php', 'version' => '8.1', 'default' => PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 1],
            ['slug' => 'php82', 'label' => 'PHP 8.2', 'category' => 'php', 'version' => '8.2', 'default' => PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 2],
            ['slug' => 'php83', 'label' => 'PHP 8.3', 'category' => 'php', 'version' => '8.3', 'default' => PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 3],
            ['slug' => 'php84', 'label' => 'PHP 8.4', 'category' => 'php', 'version' => '8.4', 'default' => PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 4],
        ];

        $toggles = $this->toggles();

        return array_map(function (array $package) use ($toggles): array {
            $installed = $this->installed($package);
            $toggle = $toggles->get($package['slug']);

            return $package + [
                'installed' => $installed,
                'service_active' => $installed && $this->active($package),
                'installable' => $this->installable($package),
                'enabled_for_clients' => $toggle?->enabled_for_clients ?? ($package['default'] && $installed),
            ];
        }, $packages);
    }

    /** @return array<int, string> */
    public function clientWebServers(): array
    {
        return collect($this->catalog())
            ->where('category', 'webserver')
            ->where('installed', true)
            ->where('service_active', true)
            ->where('enabled_for_clients', true)
            ->pluck('slug')
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    public function clientPhpVersions(): array
    {
        return collect($this->catalog())
            ->where('category', 'php')
            ->where('installed', true)
            ->where('service_active', true)
            ->where('enabled_for_clients', true)
            ->pluck('version')
            ->filter()
            ->values()
            ->all();
    }

    public function install(string $slug): void
    {
        $package = collect($this->catalog())->firstWhere('slug', $slug);
        if (! $package) {
            throw new RuntimeException('Paquete desconocido.');
        }
        if (! $package['installable']) {
            throw new RuntimeException('El paquete no está disponible en los repositorios configurados del servidor.');
        }

        app(ServerCommandRunner::class)->run([
            'sudo', '-n', (string) config('xpanel.native_hosting.package_helper'), 'install', $slug,
        ], timeout: 900);
    }

    private function installed(array $package): bool
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            if ($package['category'] === 'php') {
                return $package['version'] === PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
            }

            return in_array($package['slug'], config('xpanel.native_hosting.web_servers', []), true);
        }

        return match ($package['slug']) {
            'nginx' => is_file('/usr/sbin/nginx'),
            'apache' => is_file('/usr/sbin/apache2ctl'),
            default => is_file('/usr/sbin/php-fpm'.$package['version'])
                || is_file('/lib/systemd/system/php'.$package['version'].'-fpm.service'),
        };
    }

    private function active(array $package): bool
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return true;
        }

        $service = match ($package['slug']) {
            'nginx' => 'nginx',
            'apache' => 'apache2',
            default => 'php'.$package['version'].'-fpm',
        };

        return $this->commandSucceeds(['systemctl', 'is-active', '--quiet', $service]);
    }

    private function installable(array $package): bool
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return false;
        }

        $aptPackage = match ($package['slug']) {
            'nginx' => 'nginx',
            'apache' => 'apache2',
            default => 'php'.$package['version'].'-fpm',
        };

        return $this->commandSucceeds(['apt-cache', 'show', $aptPackage]);
    }

    private function commandSucceeds(array $command): bool
    {
        try {
            app(ServerCommandRunner::class)->run($command, timeout: 10);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function toggles()
    {
        $node = ServerNode::query()->where('is_active', true)->first();

        return $node
            ? SoftwarePackage::query()->where('server_node_id', $node->id)->get()->keyBy('slug')
            : collect();
    }
}
