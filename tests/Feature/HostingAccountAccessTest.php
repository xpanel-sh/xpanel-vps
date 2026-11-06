<?php

namespace Tests\Feature;

use App\Models\HostingAccount;
use App\Models\HostingPlan;
use App\Models\PlanOrder;
use App\Models\SystemSetting;
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

    public function test_client_can_change_plan_without_replacing_the_instance(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.host_instances.release_path', '/opt/xpanel-host/current');
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Cliente', 'domain' => 'client.test', 'user_id' => $user->id, 'status' => 'active']);
        $small = $this->plan('small', 1, 1024);
        $large = $this->plan('large', 5, 5120);
        $account = HostingAccount::create(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'hosting_plan_id' => $small->id, 'name' => 'Hosting', 'status' => 'active']);
        $instance = app(HostInstanceProvisioner::class)->create($account);

        $this->actingAs($user)->post(route('client.host.plan', $account), ['plan_id' => $large->id])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($large->id, $account->fresh()->hosting_plan_id);
        $this->assertSame($instance->id, $account->fresh()->hostInstance->id);
        $this->assertDatabaseHas('plan_orders', ['hosting_account_id' => $account->id, 'kind' => 'plan_change', 'previous_hosting_plan_id' => $small->id]);
    }

    public function test_cancellation_is_reversible_during_retention(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.host_instances.release_path', '/opt/xpanel-host/current');
        SystemSetting::set('cancelled_data_retention_days', '30');
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Cliente', 'domain' => 'client.test', 'user_id' => $user->id, 'status' => 'active']);
        $plan = $this->plan('standard', 2, 2048);
        $order = PlanOrder::create(['number' => 'XP-CANCEL', 'tenant_id' => $tenant->id, 'hosting_plan_id' => $plan->id, 'status' => 'active', 'payment_status' => 'paid', 'amount' => 10, 'currency' => 'USD', 'billing_period_months' => 1, 'payment_due_at' => now(), 'service_ends_at' => now()->addWeek()]);
        $account = HostingAccount::create(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'hosting_plan_id' => $plan->id, 'plan_order_id' => $order->id, 'name' => 'Hosting', 'status' => 'active']);
        $order->update(['hosting_account_id' => $account->id]);
        app(HostInstanceProvisioner::class)->create($account);

        $this->actingAs($user)->post(route('client.host.cancel', $account))->assertRedirect();
        $this->assertSame('cancellation_pending', $account->fresh()->status);
        $this->assertNotNull($account->fresh()->purge_after);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'admin')->post(route('admin.instances.restore', $account->hostInstance))->assertRedirect();
        $this->assertSame('active', $account->fresh()->status);
        $this->assertNull($account->fresh()->purge_after);
        $this->assertSame('active', $order->fresh()->status);
    }

    public function test_lifecycle_command_suspends_a_cancelled_hosting_after_its_period(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.host_instances.release_path', '/opt/xpanel-host/current');
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Cliente', 'domain' => 'client.test', 'user_id' => $user->id, 'status' => 'active']);
        $plan = $this->plan('lifecycle', 2, 2048);
        $order = PlanOrder::create(['number' => 'XP-LIFECYCLE', 'tenant_id' => $tenant->id, 'hosting_plan_id' => $plan->id, 'status' => PlanOrder::STATUS_CANCELLATION_PENDING, 'payment_status' => 'paid', 'amount' => 10, 'currency' => 'USD', 'billing_period_months' => 1, 'payment_due_at' => now(), 'service_ends_at' => now()->subMinute()]);
        $account = HostingAccount::create(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'hosting_plan_id' => $plan->id, 'plan_order_id' => $order->id, 'name' => 'Hosting', 'status' => 'cancellation_pending', 'suspend_at' => now()->subMinute(), 'purge_after' => now()->addDays(30)]);
        $order->update(['hosting_account_id' => $account->id]);
        $instance = app(HostInstanceProvisioner::class)->create($account);

        $this->artisan('xpanel:hosting-lifecycle')->assertSuccessful();

        $this->assertSame('suspended', $account->fresh()->status);
        $this->assertSame('suspended', $instance->fresh()->status);
        $this->assertSame(PlanOrder::STATUS_CANCELLED, $order->fresh()->status);
    }

    private function plan(string $slug, int $sites, int $storage): HostingPlan
    {
        return HostingPlan::create(['name' => ucfirst($slug), 'slug' => $slug, 'max_sites' => $sites, 'max_databases' => $sites, 'storage_mb' => $storage, 'inode_limit' => 50000, 'bandwidth_gb' => 25, 'email_accounts' => $sites, 'memory_mb' => 512, 'cpu_percent' => 50, 'tasks_max' => 128, 'monthly_price' => 10, 'billing_period_months' => 1, 'payment_due_days' => 7, 'is_active' => true]);
    }
}
