<?php

namespace App\Services;

use App\Models\HostInstance;

class HostInstanceResourceLimiter
{
    public function __construct(private ServerCommandRunner $commands) {}

    /** @return array{memory_high_mb:int,memory_max_mb:int,swap_max_mb:int,cpu_percent:int,tasks_max:int} */
    public function limitsFor(HostInstance $instance): array
    {
        $instance->loadMissing('tenant.plan');
        $plan = $instance->tenant?->plan;
        $memory = (int) ($plan?->memory_mb ?: config('xpanel.host_instances.default_limits.memory_mb'));

        return [
            'memory_high_mb' => max(64, (int) floor($memory * 0.9)),
            'memory_max_mb' => max(128, $memory),
            'swap_max_mb' => max(0, (int) ($plan?->swap_mb ?? config('xpanel.host_instances.default_limits.swap_mb'))),
            'cpu_percent' => max(10, (int) ($plan?->cpu_percent ?: config('xpanel.host_instances.default_limits.cpu_percent'))),
            'tasks_max' => max(32, (int) ($plan?->tasks_max ?: config('xpanel.host_instances.default_limits.tasks_max'))),
        ];
    }

    /** @return array<int, string> */
    public function helperArguments(HostInstance $instance): array
    {
        $limits = $this->limitsFor($instance);

        return array_map('strval', [
            $limits['memory_high_mb'],
            $limits['memory_max_mb'],
            $limits['swap_max_mb'],
            $limits['cpu_percent'],
            $limits['tasks_max'],
        ]);
    }

    public function apply(HostInstance $instance): void
    {
        if (! $instance->provisioned_at || ! config('xpanel.native_hosting.apply_system_changes')) {
            return;
        }

        $this->commands->run([
            'sudo', config('xpanel.host_instances.helper'), 'set-limits', $instance->uuid,
            ...$this->helperArguments($instance),
        ]);
    }
}
