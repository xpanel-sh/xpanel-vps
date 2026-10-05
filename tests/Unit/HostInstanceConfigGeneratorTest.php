<?php

namespace Tests\Unit;

use App\Models\HostInstance;
use App\Models\HostingAccount;
use App\Services\HostInstanceConfigGenerator;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HostInstanceConfigGeneratorTest extends TestCase
{
    private string $stagingRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stagingRoot = storage_path('framework/testing/host-instance-generator');
        config()->set('xpanel.host_instances.staging_root', $this->stagingRoot);
        config()->set('xpanel.host_instances.root', '/var/lib/xpanel-vps/instances');
        config()->set('xpanel.host_instances.control_plane_url', 'https://host.example.test');
        config()->set('xpanel.server_ip', '203.0.113.10');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->stagingRoot);
        parent::tearDown();
    }

    public function test_it_generates_an_isolated_runtime_for_each_host_instance(): void
    {
        $first = $this->makeInstance('01234567-89ab-cdef-0123-456789abcdef', 'panel.one.test');
        $first->setRelation('hostingAccount', new HostingAccount(['custom_panel_domain' => 'panel.customer.test']));
        $second = $this->makeInstance('fedcba98-7654-3210-fedc-ba9876543210', 'panel.two.test');

        $generator = app(HostInstanceConfigGenerator::class);
        $firstFiles = $generator->generate($first);
        $secondFiles = $generator->generate($second);

        $firstEnvironment = File::get($firstFiles['environment']);
        $this->assertStringContainsString('XPANEL_MANAGEMENT_MODE="vps-instance"', $firstEnvironment);
        $this->assertStringContainsString('XPANEL_SERVER_IPV4="203.0.113.10"', $firstEnvironment);
        $this->assertStringContainsString('DB_DATABASE="'.$first->database_path.'"', $firstEnvironment);
        $this->assertStringContainsString('XPANEL_SYSTEMD_SLICE="xpanel-instance-'.$first->uuid.'.slice"', $firstEnvironment);
        $this->assertStringContainsString('XPANEL_ASSIGNED_CPU_PERCENT="100"', $firstEnvironment);
        $this->assertStringContainsString('XPANEL_ASSIGNED_MEMORY_MIB="512"', $firstEnvironment);
        $this->assertStringContainsString('XPANEL_FPM_SERVICE="xpanel-instance-'.$first->uuid.'-fpm.service"', $firstEnvironment);
        $this->assertStringNotContainsString($second->uuid, $firstEnvironment);
        $this->assertNotSame($firstFiles['directory'], $secondFiles['directory']);
        $this->assertStringContainsString('php8.3-fpm-xpanel-instance-'.$first->uuid.'.sock', File::get($firstFiles['nginx']));
        $this->assertStringContainsString('listen 10000 ssl;', File::get($firstFiles['nginx']));
        $this->assertStringContainsString('server_name panel.one.test panel.customer.test;', File::get($firstFiles['nginx']));
        $this->assertStringContainsString('Slice=xpanel-instance-'.$first->uuid.'.slice', File::get($firstFiles['fpm_service']));
        $this->assertStringContainsString('/php-fpm-pools/*.conf', File::get($firstFiles['fpm_global']));
    }

    public function test_regeneration_preserves_the_instance_application_key(): void
    {
        $instance = $this->makeInstance('01234567-89ab-cdef-0123-456789abcdef', 'panel.one.test');
        $generator = app(HostInstanceConfigGenerator::class);

        $first = File::get($generator->generate($instance)['environment']);
        $second = File::get($generator->generate($instance)['environment']);

        preg_match('/^APP_KEY=(.+)$/m', $first, $firstKey);
        preg_match('/^APP_KEY=(.+)$/m', $second, $secondKey);
        $this->assertSame($firstKey[1], $secondKey[1]);
    }

    private function makeInstance(string $uuid, string $domain): HostInstance
    {
        $root = '/var/lib/xpanel-vps/instances/'.$uuid;

        return new HostInstance([
            'uuid' => $uuid,
            'panel_domain' => $domain,
            'access_port' => 10000,
            'system_user' => 'xhi'.substr(str_replace('-', '', $uuid), 0, 12),
            'release_path' => '/opt/xpanel-host/current',
            'instance_root' => $root,
            'database_path' => $root.'/database/database.sqlite',
            'broker_secret' => str_repeat('a', 64),
            'php_version' => '8.3',
        ]);
    }
}
