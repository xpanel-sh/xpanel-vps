<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\HostingPlan;
use App\Models\ServerNode;
use App\Models\SoftwarePackage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NativeSiteConfigGenerator;
use App\Services\SiteProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NativeSiteProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_tenant_receives_an_isolated_native_site_runtime(): void
    {
        config()->set('xpanel.native_hosting.enabled', true);
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.native_hosting.web_root', '/var/www/xpanel');
        config()->set('xpanel.native_hosting.web_servers', ['nginx', 'apache']);
        config()->set('xpanel.native_hosting.php_versions', ['8.3']);

        $plan = HostingPlan::create([
            'name' => 'Hosting',
            'slug' => 'hosting',
            'max_sites' => 10,
            'max_databases' => 10,
            'storage_mb' => 10240,
            'bandwidth_gb' => 100,
            'email_accounts' => 10,
            'monthly_price' => 10,
            'is_active' => true,
        ]);

        $node = ServerNode::create([
            'name' => 'Servidor local',
            'ip_address' => '127.0.0.1',
            'port' => 7070,
            'auth_token' => 'test-token',
            'is_active' => true,
        ]);
        SoftwarePackage::create([
            'server_node_id' => $node->id,
            'slug' => 'apache',
            'category' => 'webserver',
            'enabled_for_clients' => true,
        ]);
        SoftwarePackage::create([
            'server_node_id' => $node->id,
            'slug' => 'php83',
            'category' => 'php',
            'enabled_for_clients' => true,
        ]);

        $tenantA = $this->tenant($plan, 'Cliente A', 'cliente-a.test', 'CLIA');
        $tenantB = $this->tenant($plan, 'Cliente B', 'cliente-b.test', 'CLIB');
        $provisioner = app(SiteProvisioner::class);

        $siteA = $provisioner->provisionForTenant($tenantA, [
            'domain' => 'alpha.test',
            'project_type' => 'php',
            'web_server' => 'nginx',
            'php_version' => '8.3',
        ]);
        $siteB = $provisioner->provisionForTenant($tenantB, [
            'domain' => 'beta.test',
            'project_type' => 'php',
            'web_server' => 'apache',
            'php_version' => '8.3',
        ]);

        try {
            $this->assertSame('native', $siteA->provisioning_driver);
            $this->assertSame('staged', $siteA->status);
            $this->assertSame('/var/www/xpanel/clia/alpha.test', $siteA->document_root);
            $this->assertSame('/var/www/xpanel/clib/beta.test', $siteB->document_root);
            $this->assertNotSame($siteA->system_user, $siteB->system_user);
            $this->assertTrue(Domain::where('tenant_id', $tenantA->id)->where('site_id', $siteA->id)->where('type', 'primary')->exists());
            $this->assertFileExists(storage_path('app/native/nginx/alpha.test.conf'));
            $this->assertFileExists(storage_path('app/native/apache/beta.test.conf'));
            $this->assertFileExists(storage_path('app/native/php-fpm/alpha.test.conf'));
        } finally {
            $configs = app(NativeSiteConfigGenerator::class);
            $configs->remove($siteA);
            $configs->remove($siteB);
        }
    }

    private function tenant(HostingPlan $plan, string $name, string $domain, string $code): Tenant
    {
        $user = User::factory()->create(['role' => 'client']);

        return Tenant::create([
            'name' => $name,
            'domain' => $domain,
            'code' => $code,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
    }
}
