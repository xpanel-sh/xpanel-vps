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

    public function test_admin_registers_a_commercial_client_without_creating_a_host_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.clients.store'), [
            'company_name' => 'Cliente Uno',
            'domain' => 'cliente.example.com',
        ]);

        $tenant = Tenant::sole();
        $response->assertRedirect(route('admin.clients.show', $tenant));
        $this->assertNotNull($tenant->user_id);
        $this->assertFalse($tenant->access_ready);
        $this->assertStringEndsWith('@xpanel.invalid', $tenant->user->email);
        $this->assertDatabaseCount('hosting_accounts', 0);
        $this->assertDatabaseCount('host_instances', 0);

        $this->actingAs($admin, 'admin')->put(route('admin.clients.update', $tenant), [
            'company_name' => 'Cliente Uno Editado',
            'domain' => 'cliente-editado.example.com',
            'status' => 'active',
        ])->assertRedirect(route('admin.clients.show', $tenant));
        $this->assertSame('Cliente Uno Editado', $tenant->fresh()->name);
    }

    public function test_each_host_receives_its_own_admin_and_the_first_enables_client_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'admin')->post(route('admin.clients.store'), [
            'company_name' => 'Cliente', 'domain' => 'cliente.example.com',
        ]);
        $tenant = Tenant::sole();
        $plan = HostingPlan::create([
            'name' => 'Growth', 'slug' => 'growth', 'max_sites' => 5,
            'max_databases' => 5, 'storage_mb' => 10240, 'bandwidth_gb' => 100,
            'email_accounts' => 10, 'monthly_price' => 9.99, 'is_active' => true,
        ]);
        $password = 'First-Host-Password-2026';

        $this->actingAs($admin, 'admin')->post(route('admin.clients.instances.store', $tenant), [
            'name' => 'Hosting principal',
            'plan_id' => $plan->id,
            'admin_name' => 'Admin Principal',
            'admin_email' => 'principal@example.com',
            'admin_password' => $password,
        ])->assertRedirect(route('admin.clients.show', $tenant));

        $tenant->refresh()->load('user');
        $first = $tenant->hostingAccounts()->with('hostInstance')->sole();
        $this->assertTrue(Hash::check($password, $tenant->user->password));
        $this->assertSame('Admin Principal', $first->admin_name);
        $this->assertSame('principal@example.com', $first->admin_email);
        $this->assertSame($password, $first->hostInstance->initial_password);

        $this->actingAs($admin, 'admin')->post(route('admin.clients.instances.store', $tenant), [
            'name' => 'Tienda',
            'plan_id' => $plan->id,
            'admin_name' => 'Admin Tienda',
            'admin_email' => 'tienda@example.com',
            'admin_password' => 'Second-Host-Password-2026',
        ])->assertRedirect(route('admin.clients.show', $tenant));

        $second = $tenant->hostingAccounts()->where('name', 'Tienda')->with('hostInstance')->sole();
        $this->assertSame('Admin Tienda', $second->admin_name);
        $this->assertSame('tienda@example.com', $second->admin_email);
        $this->assertSame('Second-Host-Password-2026', $second->hostInstance->initial_password);
        $this->assertSame('principal@example.com', $tenant->fresh()->user->email);
    }
}
