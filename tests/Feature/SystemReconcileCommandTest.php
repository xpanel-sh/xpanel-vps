<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemReconcileCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_is_safe_when_no_host_instances_exist(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', true);

        $this->artisan('xpanel:system-reconcile', ['--repair' => true])
            ->expectsOutput('Instancias activas verificadas: 0; pendientes: 0.')
            ->assertExitCode(0);
    }

    public function test_local_development_does_not_attempt_system_repair(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);

        $this->artisan('xpanel:system-reconcile', ['--repair' => true])
            ->assertExitCode(0);
    }
}
