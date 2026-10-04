<?php

namespace Tests\Feature;

use App\Models\HostingAccount;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\HostInstanceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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

    public function test_cloud_domain_can_change_with_existing_host_instances(): void
    {
        $stagingRoot = storage_path('framework/testing/cloud-domain-migration');
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.host_instances.staging_root', $stagingRoot);
        config()->set('xpanel.host_instances.root', '/var/lib/xpanel-vps/instances');
        config()->set('xpanel.host_instances.release_path', '/opt/xpanel-host/current');
        config()->set('xpanel.host_instances.cloud_domain', 'old-cloud.example.test');
        config()->set('xpanel.server_ip', '147.93.183.63');
        config()->set('xpanel.panel_port', 8443);

        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create([
            'name' => 'Cliente', 'domain' => 'cliente.example.com',
            'user_id' => $owner->id, 'status' => 'active',
        ]);
        $account = HostingAccount::create([
            'uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id,
            'name' => 'Hosting principal', 'admin_name' => 'Owner',
            'admin_email' => $owner->email, 'status' => 'active',
        ]);
        $instance = app(HostInstanceProvisioner::class)->create($account);

        $this->actingAs($admin, 'admin')->put(route('admin.settings.update'), [
            'app_name' => 'Doolpool Cloud',
            'panel_domain' => 'cloud.doolpool.com',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $expected = 'h-'.substr(str_replace('-', '', $instance->uuid), 0, 12).'.cloud.doolpool.com';
        $this->assertSame($expected, $instance->fresh()->panel_domain);
        $this->assertSame('waiting_dns', $instance->fresh()->ssl_status);
        $this->assertSame('cloud.doolpool.com', config('xpanel.host_instances.cloud_domain'));

        File::deleteDirectory($stagingRoot);
    }
}
