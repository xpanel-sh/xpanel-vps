<?php

namespace Tests\Unit;

use App\Models\HostingPlan;
use App\Models\HostInstance;
use App\Models\Tenant;
use App\Services\HostInstanceResourceLimiter;
use Tests\TestCase;

class HostInstanceResourceLimiterTest extends TestCase
{
    public function test_it_translates_the_assigned_plan_to_safe_cgroup_limits(): void
    {
        $plan = new HostingPlan([
            'memory_mb' => 2048,
            'swap_mb' => 512,
            'cpu_percent' => 175,
            'tasks_max' => 400,
        ]);
        $tenant = new Tenant;
        $tenant->setRelation('plan', $plan);
        $instance = new HostInstance;
        $instance->setRelation('tenant', $tenant);

        $limits = app(HostInstanceResourceLimiter::class)->limitsFor($instance);

        $this->assertSame(1843, $limits['memory_high_mb']);
        $this->assertSame(2048, $limits['memory_max_mb']);
        $this->assertSame(512, $limits['swap_max_mb']);
        $this->assertSame(175, $limits['cpu_percent']);
        $this->assertSame(400, $limits['tasks_max']);
    }

    public function test_it_uses_conservative_defaults_when_a_tenant_has_no_plan(): void
    {
        config()->set('xpanel.host_instances.default_limits', [
            'memory_mb' => 512,
            'swap_mb' => 0,
            'cpu_percent' => 100,
            'tasks_max' => 256,
        ]);
        $tenant = new Tenant;
        $tenant->setRelation('plan', null);
        $instance = new HostInstance;
        $instance->setRelation('tenant', $tenant);

        $this->assertSame(['460', '512', '0', '100', '256'], app(HostInstanceResourceLimiter::class)->helperArguments($instance));
    }
}
