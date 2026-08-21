<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_displays_active_plans_and_company_content(): void
    {
        config()->set('xpanel.home_enabled', true);
        SystemSetting::set('page_home_company_name', 'Acme Hosting');
        HostingPlan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'max_sites' => 5,
            'max_databases' => 5,
            'storage_mb' => 10240,
            'bandwidth_gb' => 100,
            'email_accounts' => 10,
            'monthly_price' => 19.99,
            'is_active' => true,
        ]);

        $this->get('/')->assertOk()->assertSee('Acme Hosting')->assertSee('Pro')->assertSee('19.99');
    }

    public function test_admin_can_edit_a_public_policy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')->put(route('admin.pages.update', 'privacy'), [
            'title' => 'Privacidad Acme',
            'body' => 'Contenido legal revisado.',
        ])->assertRedirect();

        $this->get(route('pages.show', 'privacy'))
            ->assertOk()
            ->assertSee('Privacidad Acme')
            ->assertSee('Contenido legal revisado.');
    }
}
