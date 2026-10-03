<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\PlanOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_contracts_a_plan_and_receives_service_before_payment(): void
    {
        $currentPlan = $this->plan(['name' => 'Starter', 'slug' => 'starter']);
        $newPlan = $this->plan([
            'name' => 'Growth', 'slug' => 'growth', 'monthly_price' => 12.50,
            'billing_period_months' => 3, 'payment_due_days' => 10,
        ]);
        [$user, $tenant] = $this->client($currentPlan);

        $response = $this->actingAs($user)->post(route('client.plans.contract', $newPlan));

        $order = PlanOrder::sole();
        $response->assertRedirect(route('client.orders.show', $order));
        $this->assertSame($currentPlan->id, $tenant->fresh()->plan_id);
        $this->assertSame(37.50, (float) $order->amount);
        $this->assertSame(3, $order->billing_period_months);
        $this->assertSame('active', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertTrue($order->payment_due_at->isSameDay(now()->addDays(10)));
        $account = $tenant->hostingAccounts()->with('hostInstance')->sole();
        $this->assertSame($newPlan->id, $account->hosting_plan_id);
        $this->assertSame($order->id, $account->plan_order_id);
        $this->assertSame($account->id, $order->fresh()->hosting_account_id);
        $instance = $account->hostInstance;
        $this->assertSame('staged', $instance->status);
        $this->assertStringStartsWith('h-', $instance->panel_domain);
        $this->assertStringEndsWith('.'.config('xpanel.host_instances.cloud_domain'), $instance->panel_domain);
        $this->assertNotEmpty($instance->initial_password);
        $this->get(route('client.host.show'))
            ->assertOk()
            ->assertSee($account->name)
            ->assertSee($instance->panel_domain);
        $this->get(route('client.host.account', $account))
            ->assertOk()
            ->assertSee('Estamos preparando tu hosting');
        $this->get(route('client.orders.show', $order))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee('Pendiente de integración');
    }

    public function test_client_can_contract_multiple_independent_hostings(): void
    {
        $plan = $this->plan(['name' => 'Growth', 'slug' => 'growth']);
        [$user, $tenant] = $this->client(null);

        $this->actingAs($user)->post(route('client.plans.contract', $plan))->assertRedirect();
        $this->actingAs($user)->post(route('client.plans.contract', $plan))->assertRedirect();

        $this->assertCount(2, $tenant->hostingAccounts()->get());
        $this->assertCount(2, $tenant->hostInstances()->get());
        $this->assertCount(2, $tenant->planOrders()->where('status', PlanOrder::STATUS_ACTIVE)->get());
        $this->assertSame(2, $tenant->hostInstances()->distinct()->count('panel_domain'));
    }

    public function test_admin_marks_payment_without_changing_service_or_provisioning(): void
    {
        $plan = $this->plan(['name' => 'Growth', 'slug' => 'growth']);
        [, $tenant] = $this->client(null);
        $order = PlanOrder::create([
            'number' => 'XP-TEST-ACTIVATE', 'tenant_id' => $tenant->id,
            'hosting_plan_id' => $plan->id, 'amount' => 9.99, 'currency' => 'USD',
            'billing_period_months' => 1, 'payment_due_at' => now()->addWeek(),
            'status' => PlanOrder::STATUS_ACTIVE, 'payment_status' => PlanOrder::PAYMENT_PENDING,
            'activated_at' => now(), 'service_ends_at' => now()->addMonth(),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.orders.paid', $order), [
                'payment_method' => 'Transferencia',
                'payment_reference' => 'REF-001',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('Transferencia', $order->payment_method);
        $this->assertSame('REF-001', $order->payment_reference);
        $this->assertNull($tenant->fresh()->plan_id);
        $this->assertDatabaseCount('host_instances', 0);
        $this->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Transferencia');
    }

    public function test_client_cannot_open_another_tenants_order(): void
    {
        $plan = $this->plan();
        [$firstUser, $firstTenant] = $this->client($plan, 'first.test');
        [, $secondTenant] = $this->client($plan, 'second.test');
        $order = PlanOrder::create([
            'number' => 'XP-OTHER-TENANT', 'tenant_id' => $secondTenant->id,
            'hosting_plan_id' => $plan->id, 'amount' => 9.99, 'currency' => 'USD',
            'billing_period_months' => 1, 'payment_due_at' => now()->addWeek(),
            'payment_status' => PlanOrder::PAYMENT_PENDING,
        ]);

        $this->actingAs($firstUser)->get(route('client.orders.show', $order))->assertNotFound();
    }

    public function test_public_plan_selection_is_preserved_after_client_login(): void
    {
        $plan = $this->plan(['slug' => 'growth']);
        [$user] = $this->client($plan);

        $this->post(route('client.login.post'), [
            'email' => $user->email,
            'password' => 'password',
            'plan' => 'growth',
        ])->assertRedirect(route('client.plans.index', ['selected' => 'growth']));
    }

    private function plan(array $attributes = []): HostingPlan
    {
        return HostingPlan::create(array_merge([
            'name' => 'Plan', 'slug' => 'plan-'.uniqid(), 'max_sites' => 2,
            'max_databases' => 2, 'storage_mb' => 2048, 'bandwidth_gb' => 20,
            'email_accounts' => 2, 'monthly_price' => 9.99,
            'billing_period_months' => 1, 'payment_due_days' => 7, 'is_active' => true,
        ], $attributes));
    }

    private function client(?HostingPlan $plan, string $domain = 'client.test'): array
    {
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create([
            'name' => 'Cliente', 'domain' => $domain, 'user_id' => $user->id,
            'plan_id' => $plan?->id, 'status' => 'active',
        ]);

        return [$user, $tenant];
    }
}
