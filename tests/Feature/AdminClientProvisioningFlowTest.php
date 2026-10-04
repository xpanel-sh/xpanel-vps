<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminClientProvisioningFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $stagingRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stagingRoot = storage_path('framework/testing/admin-client-provisioning');
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.host_instances.staging_root', $this->stagingRoot);
        config()->set('xpanel.host_instances.root', '/var/lib/xpanel-vps/instances');
        config()->set('xpanel.host_instances.release_path', '/opt/xpanel-host/current');
        config()->set('xpanel.host_instances.cloud_domain', 'cloud.example.test');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->stagingRoot);
        parent::tearDown();
    }

    public function test_admin_creates_client_and_first_host_with_one_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = HostingPlan::create([
            'name' => 'Growth', 'slug' => 'growth', 'max_sites' => 5,
            'max_databases' => 5, 'storage_mb' => 10240, 'bandwidth_gb' => 100,
            'email_accounts' => 10, 'monthly_price' => 9.99, 'is_active' => true,
        ]);
        $password = 'One-Secure-Password-2026';

        $response = $this->actingAs($admin, 'admin')->post(route('admin.clients.store'), [
            'company_name' => 'Cliente Uno',
            'domain' => 'cliente.example.com',
            'plan_id' => $plan->id,
            'owner_name' => 'Cliente Principal',
            'owner_email' => 'cliente@example.com',
            'owner_password' => $password,
        ]);

        $tenant = Tenant::with(['user', 'hostingAccounts.hostInstance'])->sole();
        $response->assertRedirect(route('admin.clients.show', $tenant));
        $this->assertTrue(Hash::check($password, $tenant->user->password));
        $this->assertSame($plan->id, $tenant->hostingAccounts->sole()->hosting_plan_id);
        $this->assertSame($password, $tenant->hostingAccounts->sole()->hostInstance->initial_password);
        $this->assertSame('staged', $tenant->hostingAccounts->sole()->hostInstance->status);
    }

    public function test_additional_hosting_does_not_request_another_owner_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create([
            'name' => 'Cliente', 'domain' => 'cliente.example.com',
            'user_id' => $owner->id, 'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.clients.instances.store', $tenant), ['name' => 'Segundo hosting'])
            ->assertRedirect(route('admin.clients.show', $tenant));

        $instance = $tenant->hostInstances()->sole();
        $this->assertSame('staged', $instance->status);
        $this->assertIsString($instance->initial_password);
        $this->assertGreaterThanOrEqual(16, strlen($instance->initial_password));
    }
}
