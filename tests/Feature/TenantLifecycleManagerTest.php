<?php

namespace Tests\Feature;

use App\Models\ServerNode;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NativeSiteConfigGenerator;
use App\Services\TenantLifecycleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantLifecycleManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspension_updates_the_tenant_and_every_native_site(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create([
            'name' => 'Cliente suspendible',
            'domain' => 'cliente.test',
            'code' => 'XSUSPEND',
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $node = ServerNode::create([
            'name' => 'Servidor local',
            'ip_address' => '127.0.0.1',
            'port' => 7070,
            'auth_token' => 'test-token',
            'is_active' => true,
        ]);
        $site = Site::create([
            'tenant_id' => $tenant->id,
            'server_node_id' => $node->id,
            'domain' => 'suspendido.test',
            'project_type' => 'php',
            'web_server' => 'nginx',
            'php_version' => '8.3',
            'provisioning_driver' => 'native',
            'status' => 'active',
        ]);
        $site->ensureNativeRuntime();
        $lifecycle = app(TenantLifecycleManager::class);

        try {
            $lifecycle->setSuspended($tenant, true);

            $this->assertSame('suspended', $tenant->fresh()->status);
            $this->assertSame('suspended', $site->fresh()->status);
            $this->assertStringContainsString('return 503', file_get_contents(storage_path('app/native/nginx/suspendido.test.conf')));

            $lifecycle->setSuspended($tenant->fresh(), false);
            $this->assertSame('active', $tenant->fresh()->status);
            $this->assertSame('staged', $site->fresh()->status);
            $this->assertStringNotContainsString('return 503', file_get_contents(storage_path('app/native/nginx/suspendido.test.conf')));
        } finally {
            app(NativeSiteConfigGenerator::class)->remove($site);
        }
    }
}
