<?php

namespace Tests\Feature;

use App\Models\HostBrokerResource;
use App\Models\HostingAccount;
use App\Models\HostingPlan;
use App\Models\HostInstance;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminHostingPortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_sites_redirect_to_the_unified_domain_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')->get(route('admin.sites.index'))
            ->assertRedirect(route('admin.domains.index'));
    }

    public function test_domain_inventory_and_hosting_detail_include_managed_resources(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'DoolPool', 'domain' => null, 'user_id' => $owner->id, 'status' => 'active']);
        $plan = HostingPlan::create([
            'name' => 'Growth', 'slug' => 'growth', 'max_sites' => 5, 'max_databases' => 5,
            'storage_mb' => 10240, 'memory_mb' => 2048, 'cpu_percent' => 200,
            'tasks_max' => 512, 'monthly_price' => 9.99,
        ]);
        $account = HostingAccount::create([
            'uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id,
            'hosting_plan_id' => $plan->id, 'name' => 'Hosting principal', 'status' => 'active',
        ]);
        $uuid = (string) Str::uuid();
        $instance = HostInstance::create([
            'tenant_id' => $tenant->id, 'hosting_account_id' => $account->id,
            'uuid' => $uuid, 'panel_domain' => 'panel.example.test', 'system_user' => 'xhi'.substr(str_replace('-', '', $uuid), 0, 12),
            'release_path' => '/opt/xpanel-host/current', 'instance_root' => '/var/lib/xpanel-vps/instances/'.$uuid,
            'database_path' => '/var/lib/xpanel-vps/instances/'.$uuid.'/database.sqlite', 'php_version' => '8.3',
            'status' => 'active', 'version' => 'abc123',
        ]);
        HostBrokerResource::create(['host_instance_id' => $instance->id, 'type' => 'site-domain', 'name' => 'example.test']);

        $this->actingAs($admin, 'admin')->get(route('admin.domains.index'))
            ->assertOk()->assertSee('Dominios y sitios')->assertSee('example.test')->assertSee('Hosting principal');

        $this->actingAs($admin, 'admin')->get(route('admin.instances.show', $instance))
            ->assertOk()->assertSee('Hosting principal')->assertSee('example.test')->assertSee('Historial de pagos');
    }
}
