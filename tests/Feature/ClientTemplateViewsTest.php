<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTemplateViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_uses_the_store_client_structure_without_custom_css(): void
    {
        config()->set('xpanel.home_enabled', true);

        $response = $this->get('/')
            ->assertOk()
            ->assertSee('id="headerContainer"', false)
            ->assertSee('class="kt-container-fixed', false)
            ->assertSee('footer mt-auto shrink-0', false)
            ->assertDontSee('home-grid', false)
            ->assertDontSee('plan-card', false);

        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());

        $main = $document->getElementById('content');
        $footer = $document->getElementsByTagName('footer')->item(0);

        $this->assertNotNull($main);
        $this->assertNotNull($footer);
        $this->assertTrue($main->parentNode->isSameNode($footer->parentNode));
    }

    public function test_authenticated_client_can_open_store_dashboard_account_and_plans(): void
    {
        $plan = HostingPlan::create([
            'name' => 'Profesional', 'slug' => 'profesional', 'max_sites' => 10,
            'max_databases' => 10, 'storage_mb' => 20480, 'bandwidth_gb' => 200,
            'email_accounts' => 20, 'monthly_price' => 29.99, 'is_active' => true,
        ]);
        $user = User::factory()->create(['role' => 'client']);
        Tenant::create([
            'name' => 'Cliente Plantilla', 'domain' => 'cliente.test', 'user_id' => $user->id,
            'plan_id' => $plan->id, 'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Panel cliente')
            ->assertSee('Administrar hosting');

        $this->get(route('client.account.show'))
            ->assertOk()
            ->assertSee('Información personal')
            ->assertSee('Cliente Plantilla')
            ->assertSee('mt-auto shrink-0', false);

        $this->get(route('client.plans.index'))
            ->assertOk()
            ->assertSee('Planes de hosting')
            ->assertSee('Plan actual: Profesional');
    }
}
