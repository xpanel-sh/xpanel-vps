<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\HostInstanceProvisioner;
use App\Services\HostInstanceCertificateProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HostInstanceProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private string $stagingRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stagingRoot = storage_path('framework/testing/host-instance-provisioning');
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.host_instances.staging_root', $this->stagingRoot);
        config()->set('xpanel.host_instances.root', '/var/lib/xpanel-vps/instances');
        config()->set('xpanel.host_instances.release_path', '/opt/xpanel-host/current');
        config()->set('xpanel.server_ip', '203.0.113.10');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->stagingRoot);
        parent::tearDown();
    }

    public function test_it_creates_a_staged_isolated_host_instance_for_a_tenant(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create([
            'name' => 'Cliente Uno',
            'domain' => 'cliente.test',
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $instance = app(HostInstanceProvisioner::class)->create($tenant, 'panel.cliente.test');

        $this->assertSame('staged', $instance->status);
        $this->assertSame($tenant->id, $instance->tenant_id);
        $this->assertSame('/var/lib/xpanel-vps/instances/'.$instance->uuid, $instance->instance_root);
        $this->assertFileExists($this->stagingRoot.'/'.$instance->uuid.'/instance.env');
        $this->assertSame($instance->id, $tenant->fresh()->hostInstance->id);
        $this->assertSame(10000, $instance->access_port);
        $this->assertSame('https://203.0.113.10:10000', $instance->fallbackUrl());
    }

    public function test_ssl_waits_without_breaking_the_instance_until_dns_points_to_the_server(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create([
            'name' => 'Cliente DNS', 'domain' => 'dns.test', 'user_id' => $user->id, 'status' => 'active',
        ]);
        $instance = app(HostInstanceProvisioner::class)->create($tenant, 'panel.dns.test');
        $instance->update(['status' => 'active']);

        $issued = app(HostInstanceCertificateProvisioner::class)->issue($instance->load('tenant.user'));

        $this->assertFalse($issued);
        $this->assertSame('active', $instance->fresh()->status);
        $this->assertSame('waiting_dns', $instance->fresh()->ssl_status);
        $this->assertStringContainsString('203.0.113.10', $instance->fresh()->ssl_last_error);
    }

    public function test_custom_domain_certificate_does_not_require_the_old_domain(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create([
            'name' => 'Cliente', 'domain' => 'client.test', 'user_id' => $user->id, 'status' => 'active',
        ]);
        $instance = app(HostInstanceProvisioner::class)->create($tenant, 'misspelled.invalid');
        $instance->update(['status' => 'active']);
        $instance->hostingAccount->update([
            'custom_panel_domain' => 'panel.customer.test', 'custom_domain_status' => 'waiting_dns',
        ]);
        config()->set('xpanel.native_hosting.apply_system_changes', true);
        $commands = \Mockery::mock(\App\Services\ServerCommandRunner::class);
        $commands->shouldReceive('run')->once()->with([
            'sudo', '-n', config('xpanel.host_instances.helper'), 'ssl-issue',
            $instance->uuid, 'panel.customer.test', $user->email,
        ], null, 300)->andReturn('active');
        $certificates = \Mockery::mock(HostInstanceCertificateProvisioner::class, [$commands])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $certificates->shouldReceive('addresses')->once()->with('panel.customer.test')->andReturn(['203.0.113.10']);

        $this->assertTrue($certificates->issue($instance->fresh()));
        $this->assertSame('https://panel.customer.test', $instance->fresh()->panelUrl());
        $environment = File::get(app(\App\Services\HostInstanceConfigGenerator::class)->generate($instance->fresh())['environment']);
        $this->assertStringContainsString('APP_URL="https://panel.customer.test"', $environment);
        $this->assertSame('https://203.0.113.10:10000', $instance->fresh()->fallbackUrl());
    }

    public function test_invalid_custom_dns_reports_the_requested_domain(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create([
            'name' => 'Cliente', 'domain' => 'client.test', 'user_id' => $user->id, 'status' => 'active',
        ]);
        $instance = app(HostInstanceProvisioner::class)->create($tenant, 'old.invalid');
        $instance->update(['status' => 'active']);
        $instance->hostingAccount->update(['custom_panel_domain' => 'panel.customer.test']);
        $commands = \Mockery::mock(\App\Services\ServerCommandRunner::class);
        $commands->shouldNotReceive('run');
        $certificates = \Mockery::mock(HostInstanceCertificateProvisioner::class, [$commands])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $certificates->shouldReceive('addresses')->once()->with('panel.customer.test')->andReturn([]);

        $this->assertFalse($certificates->issue($instance->fresh()));
        $this->assertStringContainsString('panel.customer.test', $instance->fresh()->hostingAccount->custom_domain_last_error);
        $this->assertStringNotContainsString('old.invalid', $instance->fresh()->ssl_last_error);
    }
}
