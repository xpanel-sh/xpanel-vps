<?php

namespace App\Services;

use App\Models\HostInstance;

class HostInstanceMetrics
{
    public function __construct(
        private readonly ServerCommandRunner $commands,
        private readonly HostInstanceResourceLimiter $limits,
    ) {}

    /** @return array<string, int|string|null> */
    public function for(HostInstance $instance): array
    {
        $limits = $this->limits->limitsFor($instance);
        [, $storageMib, $inodeLimit] = $this->limits->diskArguments($instance);
        $values = ['memory_bytes' => 0, 'tasks' => 0, 'cpu_nsec' => 0, 'disk_bytes' => 0];
        $error = null;

        if ($instance->provisioned_at && config('xpanel.native_hosting.apply_system_changes')) {
            try {
                $output = $this->commands->run([
                    'sudo', '-n', (string) config('xpanel.host_instances.helper'),
                    'instance-metrics', $instance->uuid, $instance->system_user,
                ], timeout: 30);
                foreach (preg_split('/\R/', $output) ?: [] as $line) {
                    if (preg_match('/^(memory_bytes|tasks|cpu_nsec|disk_bytes)=([0-9]+)$/', trim($line), $match)) {
                        $values[$match[1]] = (int) $match[2];
                    }
                }
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        return [
            ...$values,
            'memory_limit_bytes' => $limits['memory_max_mb'] * 1024 * 1024,
            'tasks_limit' => $limits['tasks_max'],
            'cpu_limit_percent' => $limits['cpu_percent'],
            'storage_limit_bytes' => $storageMib * 1024 * 1024,
            'inode_limit' => $inodeLimit,
            'error' => $error,
        ];
    }
}
