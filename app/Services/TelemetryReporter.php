<?php

namespace App\Services;

use App\Models\HostInstance;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class TelemetryReporter
{
    public const CATEGORIES = ['version', 'deployment', 'health', 'os', 'compute', 'storage', 'gpu', 'workload'];

    public function preferences(): array
    {
        $selected = json_decode(SystemSetting::get('telemetry_categories', json_encode(self::CATEGORIES)), true);

        return [
            'enabled' => SystemSetting::get('telemetry_enabled', '1') === '1',
            'categories' => array_values(array_intersect(self::CATEGORIES, is_array($selected) ? $selected : self::CATEGORIES)),
        ];
    }

    public function save(bool $enabled, array $categories): void
    {
        SystemSetting::set('telemetry_enabled', $enabled ? '1' : '0');
        SystemSetting::set('telemetry_categories', json_encode(array_values(array_intersect(self::CATEGORIES, $categories))));
    }

    public function send(): bool
    {
        $preferences = $this->preferences();
        if (! $preferences['enabled']) {
            return false;
        }

        try {
            $response = Http::asJson()->timeout(5)->post(config('xpanel.telemetry.endpoint'), $this->payload($preferences['categories']));
        } catch (Throwable) {
            return false;
        }

        return $response->successful();
    }

    public function payload(?array $categories = null): array
    {
        $categories ??= $this->preferences()['categories'];
        $payload = [
            'schema' => 1,
            'installation_id' => $this->installationId(),
            'product' => 'xpanel-vps',
            'sent_at' => now()->utc()->toIso8601String(),
        ];
        if (in_array('version', $categories, true)) {
            $payload['version'] = ['revision' => $this->revision(), 'php' => PHP_VERSION];
        }
        if (in_array('deployment', $categories, true)) {
            $payload['deployment'] = ['mode' => 'vps-control-plane', 'container' => is_file('/.dockerenv')];
        }
        if (in_array('health', $categories, true)) {
            $payload['health'] = ['uptime_seconds' => $this->uptime(), 'load_average' => sys_getloadavg() ?: []];
        }
        if (in_array('os', $categories, true)) {
            $payload['os'] = $this->os();
        }
        if (in_array('compute', $categories, true)) {
            $payload['compute'] = $this->compute();
        }
        if (in_array('storage', $categories, true)) {
            $payload['storage'] = $this->storage();
        }
        if (in_array('gpu', $categories, true)) {
            $payload['gpu'] = $this->gpu();
        }
        if (in_array('workload', $categories, true)) {
            $payload['workload'] = Schema::hasTable('host_instances') ? [
                'host_instances_total' => HostInstance::query()->count(),
                'host_instances_active' => HostInstance::query()->where('status', 'active')->count(),
            ] : ['host_instances_total' => 0, 'host_instances_active' => 0];
        }

        return $payload;
    }

    private function installationId(): string
    {
        $id = SystemSetting::get('telemetry_installation_id');
        if (! Str::isUuid($id)) {
            $id = (string) Str::uuid();
            SystemSetting::set('telemetry_installation_id', $id);
        }

        return $id;
    }

    private function revision(): string
    {
        foreach ([base_path('.git/refs/heads/main'), '/var/lib/xpanel-vps/installed-revision'] as $path) {
            if (is_file($path) && ($value = trim((string) @file_get_contents($path))) !== '') {
                return substr($value, 0, 64);
            }
        }

        return 'unknown';
    }

    private function uptime(): int
    {
        return (int) floor((float) explode(' ', trim((string) @file_get_contents('/proc/uptime')))[0]);
    }

    private function os(): array
    {
        $release = @parse_ini_file('/etc/os-release') ?: [];

        return ['family' => PHP_OS_FAMILY, 'distribution' => $release['ID'] ?? 'unknown', 'version' => $release['VERSION_ID'] ?? 'unknown', 'kernel' => php_uname('r'), 'architecture' => php_uname('m')];
    }

    private function compute(): array
    {
        $cpu = (string) @file_get_contents('/proc/cpuinfo');
        preg_match('/^model name\s*:\s*(.+)$/m', $cpu, $model);
        $memory = (string) @file_get_contents('/proc/meminfo');
        preg_match('/^MemTotal:\s+(\d+)/m', $memory, $total);
        preg_match('/^MemAvailable:\s+(\d+)/m', $memory, $available);

        return ['logical_cpus' => max(1, substr_count($cpu, "processor\t:")), 'cpu_model' => trim($model[1] ?? 'unknown'), 'memory_total_mb' => intdiv((int) ($total[1] ?? 0), 1024), 'memory_available_mb' => intdiv((int) ($available[1] ?? 0), 1024)];
    }

    private function storage(): array
    {
        return ['root_total_gb' => round((float) disk_total_space('/') / 1073741824, 2), 'root_free_gb' => round((float) disk_free_space('/') / 1073741824, 2)];
    }

    private function gpu(): array
    {
        $devices = [];
        foreach (glob('/sys/class/drm/card[0-9]*/device') ?: [] as $device) {
            $devices[] = ['vendor_id' => trim((string) @file_get_contents($device.'/vendor')), 'device_id' => trim((string) @file_get_contents($device.'/device'))];
        }

        return ['count' => count($devices), 'devices' => $devices];
    }
}
