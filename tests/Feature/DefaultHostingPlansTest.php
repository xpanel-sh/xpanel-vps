<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\User;
use Database\Seeders\DefaultDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultHostingPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_four_new_plans_are_drafts_with_conservative_resource_limits(): void
    {
        app(DefaultDataSeeder::class)->run();

        $expected = [
            'esencial' => [1, 1, 2048, 512, 25],
            'plus' => [3, 3, 5120, 768, 75],
            'pro' => [5, 5, 10240, 1024, 150],
            'max' => [10, 10, 20480, 1536, 300],
        ];
        foreach ($expected as $slug => [$sites, $databases, $storage, $memory, $bandwidth]) {
            $plan = HostingPlan::where('slug', $slug)->firstOrFail();
            $this->assertSame([$sites, $databases, $storage, $memory, $bandwidth], [
                $plan->max_sites, $plan->max_databases, $plan->storage_mb, $plan->memory_mb, $plan->bandwidth_gb,
            ]);
            $this->assertFalse($plan->is_active);
        }
        $this->assertDatabaseCount('hosting_plans', 4);
    }

    public function test_reseeding_does_not_change_existing_customer_plan_terms(): void
    {
        $legacy = HostingPlan::create([
            'name' => 'Starter', 'slug' => 'starter', 'max_sites' => 2,
            'max_databases' => 2, 'storage_mb' => 4096, 'monthly_price' => 8,
            'is_active' => true,
        ]);
        app(DefaultDataSeeder::class)->run();
        $this->assertSame(2, $legacy->fresh()->max_sites);
        $this->assertSame('8.00', $legacy->fresh()->monthly_price);
        $this->assertFalse($legacy->fresh()->is_active);
    }

    public function test_draft_cannot_be_activated_without_price_or_hard_disk_quota(): void
    {
        app(DefaultDataSeeder::class)->run();
        $plan = HostingPlan::where('slug', 'esencial')->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')->post(route('admin.plans.toggle', $plan))
            ->assertSessionHasErrors('monthly_price');
        $this->assertFalse($plan->fresh()->is_active);

        $plan->update(['monthly_price' => 5]);
        $this->actingAs($admin, 'admin')->post(route('admin.plans.toggle', $plan))
            ->assertSessionHasErrors('storage_mb');
        $this->assertFalse($plan->fresh()->is_active);
    }
}
