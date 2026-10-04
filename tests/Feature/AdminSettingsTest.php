<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_prepare_the_cloud_domain_from_settings(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.server_ip', '147.93.183.63');
        config()->set('xpanel.panel_port', 8443);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'app_name' => 'Doolpool Cloud',
                'panel_domain' => 'cloud.doolpool.com',
            ])
            ->assertRedirect();

        $this->assertSame('Doolpool Cloud', SystemSetting::get('app_name'));
        $this->assertSame('cloud.doolpool.com', SystemSetting::get('panel_domain'));
        $this->assertSame('staged', SystemSetting::get('panel_domain_status'));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('https://147.93.183.63:8443')
            ->assertSee('cloud.doolpool.com');
    }
}
