<?php

namespace Tests\Feature;

use App\Models\HostingAccount;
use App\Models\HostingPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\HostInstanceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HostingAccountAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_receives_a_short_lived_sso_link_for_the_selected_hosting(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.host_instances.release_path', '/opt/xpanel-host/current');
        config()->set('xpanel.server_ip', '203.0.113.10');
        $user = User::factory()->create(['role' => 'client']);
        $plan = HostingPlan::create([
            'name' => 'Starter', 'slug' => 'starter', 'max_sites' => 1, 'max_databases' => 1,
            'storage_mb' => 1024, 'bandwidth_gb' => 10, 'email_accounts' => 1,
            'monthly_price' => 5, 'billing_period_months' => 1, 'payment_due_days' => 7, 'is_active' => true,
        ]);
        $tenant = Tenant::create(['name' => 'Cliente', 'domain' => 'client.test', 'user_id' => $user->id, 'status' => 'active']);
        $account = HostingAccount::create([
            'uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'hosting_plan_id' => $plan->id,
            'name' => 'Hosting principal', 'admin_name' => 'Administrador Hosting',
            'admin_email' => 'hosting-owner@example.com', 'status' => 'active',
        ]);
        $instance = app(HostInstanceProvisioner::class)->create($account);
        $instance->update(['status' => 'active', 'ssl_status' => 'active']);

        $response = $this->actingAs($user)->post(route('client.host.access', $account));

        $response->assertRedirectContains('https://'.$instance->panel_domain.'/auth/control-plane?token=');
        $token = explode('token=', $response->headers->get('Location'), 2)[1];
        $this->assertCount(2, explode('.', $token));
        [$payload] = explode('.', $token);
        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('hosting-owner@example.com', $decoded['email']);
        $this->assertSame('Administrador Hosting', $decoded['name']);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'admin')
            ->get(route('admin.instances.access', $instance))
            ->assertRedirectContains('https://'.config('xpanel.server_ip').':'.$instance->access_port.'/auth/control-plane?token=');
    }

    public function test_client_cannot_access_another_clients_hosting(): void
    {
        $owner = User::factory()->create(['role' => 'client']);
        $outsider = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Owner', 'domain' => 'owner.test', 'user_id' => $owner->id, 'status' => 'active']);
        Tenant::create(['name' => 'Other', 'domain' => 'other.test', 'user_id' => $outsider->id, 'status' => 'active']);
        $account = HostingAccount::create([
            'uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'name' => 'Privado', 'status' => 'active',
        ]);

        $this->actingAs($outsider)->get(route('client.host.account', $account))->assertNotFound();
    }
}
