<?php

namespace Tests\Feature;

use App\Models\ManagedDatabase;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NativeDatabaseProvisioner;
use App\Services\ServerCommandRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NativeDatabaseProvisionerTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_is_staged_when_system_changes_are_disabled(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        $database = $this->database();

        app(NativeDatabaseProvisioner::class)->create($database, 'SafePassword_123!');

        $this->assertSame('staged', $database->fresh()->status);
    }

    public function test_database_is_created_through_the_privileged_native_helper(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', true);
        config()->set('xpanel.native_hosting.site_helper', '/usr/local/lib/xpanel-vps/site-helper');
        $database = $this->database();
        $runner = Mockery::mock(ServerCommandRunner::class);
        $runner->shouldReceive('run')->once()->with(
            ['sudo', '-n', '/usr/local/lib/xpanel-vps/site-helper', 'database-create', 'x123_demo', 'x123_user'],
            "SafePassword_123!\n"
        )->andReturn('');
        $this->app->instance(ServerCommandRunner::class, $runner);

        app(NativeDatabaseProvisioner::class)->create($database, 'SafePassword_123!');

        $this->assertSame('active', $database->fresh()->status);
    }

    private function database(): ManagedDatabase
    {
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create([
            'name' => 'Cliente DB',
            'domain' => 'cliente-db.test',
            'code' => 'X123',
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        return ManagedDatabase::create([
            'tenant_id' => $tenant->id,
            'name' => 'x123_demo',
            'username' => 'x123_user',
            'password' => bcrypt('SafePassword_123!'),
            'engine' => 'mariadb',
            'status' => 'provisioning',
        ]);
    }
}
