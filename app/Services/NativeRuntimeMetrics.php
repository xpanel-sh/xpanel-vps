<?php

namespace App\Services;

use App\Models\DnsRecord;
use App\Models\EmailAccount;
use App\Models\HostBrokerOperation;
use App\Models\ManagedDatabase;
use Illuminate\Support\Facades\Cache;

class NativeRuntimeMetrics
{
    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        [$cpuTotal, $cpuIdle] = $this->cpuTicks();
        $previous = Cache::get('xpanel-vps:native-runtime-cpu');
        Cache::put('xpanel-vps:native-runtime-cpu', [
            'total' => $cpuTotal,
            'idle' => $cpuIdle,
        ], now()->addMinutes(15));

        $cpuPercent = null;
        if (is_array($previous) && $cpuTotal > 0) {
            $totalDelta = $cpuTotal - (int) ($previous['total'] ?? $cpuTotal);
            $idleDelta = $cpuIdle - (int) ($previous['idle'] ?? $cpuIdle);
            if ($totalDelta > 0) {
                $cpuPercent = round(min(100, max(0, ($totalDelta - $idleDelta) / $totalDelta * 100)), 2);
            }
        }

        [$memoryTotal, $memoryAvailable] = $this->memory();
        $memoryUsed = max(0, $memoryTotal - $memoryAvailable);
        $diskTotal = (int) (@disk_total_space('/') ?: @disk_total_space(base_path()) ?: 0);
        $diskFree = (int) (@disk_free_space('/') ?: @disk_free_space(base_path()) ?: 0);
        $diskUsed = max(0, $diskTotal - $diskFree);

        return [
            'source' => 'linux-native',
            'sampled_at' => now()->toIso8601String(),
            'system' => [
                'cpu_percent' => $cpuPercent,
                'memory' => $this->usage($memoryUsed, $memoryTotal),
                'disk' => $this->usage($diskUsed, $diskTotal),
                'processes' => count(glob('/proc/[0-9]*', GLOB_ONLYDIR) ?: []),
                'load' => function_exists('sys_getloadavg') ? (sys_getloadavg() ?: []) : [],
            ],
            'resources' => [
                'databases' => ManagedDatabase::query()->count(),
                'dns_records' => DnsRecord::query()->count(),
                'mail_accounts' => EmailAccount::query()->count(),
                'operations' => HostBrokerOperation::query()->count(),
            ],
        ];
    }

    /** @return array{int,int} */
    private function cpuTicks(): array
    {
        $stat = @file('/proc/stat');
        $line = is_array($stat) ? ($stat[0] ?? '') : '';
        $fields = preg_split('/\s+/', trim((string) $line)) ?: [];
        if (($fields[0] ?? null) !== 'cpu') {
            return [0, 0];
        }
        array_shift($fields);
        $ticks = array_map('intval', $fields);

        return [array_sum($ticks), ($ticks[3] ?? 0) + ($ticks[4] ?? 0)];
    }

    /** @return array{int,int} */
    private function memory(): array
    {
        $memory = (string) @file_get_contents('/proc/meminfo');
        preg_match('/^MemTotal:\s+(\d+)\s+kB/m', $memory, $total);
        preg_match('/^MemAvailable:\s+(\d+)\s+kB/m', $memory, $available);

        return [(int) ($total[1] ?? 0) * 1024, (int) ($available[1] ?? 0) * 1024];
    }

    /** @return array{used:int,total:int,percent:?float} */
    private function usage(int $used, int $total): array
    {
        return [
            'used' => $used,
            'total' => $total,
            'percent' => $total > 0 ? round(min(100, max(0, $used / $total * 100)), 2) : null,
        ];
    }
}
