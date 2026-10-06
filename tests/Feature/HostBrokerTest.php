<?php

namespace Tests\Feature;

use App\Models\HostBrokerOperation;
use App\Models\HostInstance;
use App\Models\HostingAccount;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NativePackageManager;
use App\Services\HostInstanceDatabaseReader;
use App\Services\ServerCommandRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PDO;
use Mockery;
use Tests\TestCase;

class HostBrokerTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/broker-instances');
        config()->set('xpanel.host_instances.root', str_replace('\\', '/', $this->root));
        config()->set('xpanel.native_hosting.apply_system_changes', false);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_native_broker_inspects_private_instance_database_through_scoped_helper(): void
    {
        [$instance] = $this->instanceWithSite();
        File::delete($instance->database_path);
        config()->set('xpanel.native_hosting.apply_system_changes', true);
        config()->set('xpanel.host_instances.broker_helper', '/opt/xpanel-vps/scripts/xpanel-host-broker-helper.sh');
        $expected = [
            'sudo', '-n', '/opt/xpanel-vps/scripts/xpanel-host-broker-helper.sh', 'inspect',
            $instance->uuid, $instance->system_user, $instance->instance_root, 'site', 'example.test', '',
        ];
        $this->mock(ServerCommandRunner::class)
            ->shouldReceive('run')->once()->with($expected, null, 15)
            ->andReturn('{"id":1,"domain":"example.test"}');

        $row = app(HostInstanceDatabaseReader::class)->query($instance, 'site', 'example.test');

        $this->assertSame('example.test', $row['domain']);
    }

    public function test_inspector_rejects_unapproved_queries(): void
    {
        [$instance] = $this->instanceWithSite();

        $this->expectException(\RuntimeException::class);
        HostInstanceDatabaseReader::inspectPath($instance->database_path, 'arbitrary-sql', 'example.test');
    }

    public function test_a_signed_instance_can_stage_an_authorized_site_operation(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        $siteRoot = '/home/'.$instance->system_user.'/public_html/example.test';
        $arguments = [
            'example.test', 'nginx', 'php', '8.3',
            $siteRoot,
            'xps'.substr(str_replace('-', '', $instance->uuid), 0, 6).'1'.substr(hash('sha256', 'example.test'), 0, 8),
            $siteRoot.'/public',
            '-', '0', 'active', 'system', '-',
        ];
        $payload = $this->payload($instance, 'apply', $arguments);

        $signature = $this->signature($payload, $secret);
        $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $signature])
            ->assertOk()->assertJson(['ok' => true, 'output' => 'authorized-staged']);
        $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $signature])
            ->assertStatus(422);

        $this->assertDatabaseHas('host_broker_operations', [
            'host_instance_id' => $instance->id, 'action' => 'apply', 'status' => 'staged',
        ]);
        $this->assertDatabaseHas('host_broker_resources', [
            'host_instance_id' => $instance->id, 'type' => 'site-domain', 'name' => 'example.test',
        ]);
    }

    public function test_tampering_with_a_broker_request_is_rejected(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        $payload = $this->payload($instance, 'database-remove', ['xp_bad_database', 'xp_bad_user']);
        $signature = $this->signature($payload, $secret);
        $payload['action'] = 'apply';

        $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $signature])->assertStatus(422);
        $this->assertSame(0, HostBrokerOperation::count());
    }

    public function test_node_runtime_and_wildcard_are_reserved_for_the_owning_instance(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        $pdo = new PDO('sqlite:'.$instance->database_path);
        $pdo->exec("UPDATE sites SET type = 'node', node_version = '22', runtime_port = 32123, wildcard_domain = 1 WHERE domain = 'example.test'");
        $siteUser = 'xps'.substr(str_replace('-', '', $instance->uuid), 0, 6).'1'.substr(hash('sha256', 'example.test'), 0, 8);
        $siteRoot = '/home/'.$instance->system_user.'/public_html/example.test';
        $payload = $this->payload($instance, 'apply', [
            'example.test', 'nginx', 'node', '8.3',
            $siteRoot, $siteUser,
            $siteRoot.'/public', '22', '32123', 'active', 'system', '-',
        ]);

        $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $this->signature($payload, $secret)])
            ->assertOk();

        $this->assertDatabaseHas('host_broker_resources', ['type' => 'runtime-port', 'name' => '32123']);
        $this->assertDatabaseHas('host_broker_resources', ['type' => 'site-domain', 'name' => '*.example.test']);
    }

    public function test_php_profile_must_match_the_instance_database(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        $pdo = new PDO('sqlite:'.$instance->database_path);
        $pdo->exec("INSERT INTO php_profiles (id, php_version, extensions) VALUES (7, '8.3', '[\"curl\",\"mysql\"]')");
        $pdo->exec("UPDATE sites SET php_profile_id = 7 WHERE domain = 'example.test'");
        $siteRoot = '/home/'.$instance->system_user.'/public_html/example.test';
        $siteUser = 'xps'.substr(str_replace('-', '', $instance->uuid), 0, 6).'1'.substr(hash('sha256', 'example.test'), 0, 8);
        $arguments = ['example.test', 'nginx', 'php', '8.3', $siteRoot, $siteUser, $siteRoot.'/public', '-', '0', 'active', 'i0123456789ab-p7', 'curl,mysql'];
        $payload = $this->payload($instance, 'apply', $arguments);
        $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $this->signature($payload, $secret)])->assertOk();

        $arguments[11] = 'curl';
        $payload = $this->payload($instance, 'apply', $arguments);
        $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $this->signature($payload, $secret)])->assertStatus(422);
    }

    public function test_wildcard_certificate_action_is_authorized_without_persisting_its_secret(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        (new PDO('sqlite:'.$instance->database_path))->exec("UPDATE sites SET wildcard_domain = 1 WHERE domain = 'example.test'");
        $siteUser = 'xps'.substr(str_replace('-', '', $instance->uuid), 0, 6).'1'.substr(hash('sha256', 'example.test'), 0, 8);
        $payload = $this->payload($instance, 'ssl-wildcard-issue', [
            'example.test', 'nginx', '/home/'.$instance->system_user.'/public_html/example.test/public',
            'admin@example.test', $siteUser,
        ]);
        $payload['input'] = base64_encode("cloudflare-secret-token-value\n");

        $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $this->signature($payload, $secret)])
            ->assertOk();

        $operation = HostBrokerOperation::latest('id')->firstOrFail();
        $this->assertStringNotContainsString('cloudflare-secret', json_encode($operation->toArray()));
    }

    public function test_certificate_inspection_is_scoped_to_a_site_in_the_instance(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        $payload = $this->payload($instance, 'ssl-inspect', ['example.test']);

        $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $this->signature($payload, $secret)])
            ->assertOk();

        $foreign = $this->payload($instance, 'ssl-inspect', ['foreign.example.test']);
        $this->postJson(route('api.host-broker'), $foreign, ['X-XPanel-Signature' => $this->signature($foreign, $secret)])
            ->assertStatus(422);
    }

    public function test_instance_can_request_its_own_custom_panel_domain(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        $payload = $this->payload($instance, 'panel-domain-set', ['panel.customer.test']);

        $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $this->signature($payload, $secret)])
            ->assertOk()
            ->assertJson(['ok' => true, 'output' => 'url=https://panel.customer.test']);

        $this->assertSame('panel.customer.test', $instance->hostingAccount->fresh()->custom_panel_domain);
        $this->assertSame('waiting_dns', $instance->hostingAccount->fresh()->custom_domain_status);
    }

    public function test_engine_status_exposes_only_enabled_supported_engines_without_granting_installation(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        $packages = Mockery::mock(NativePackageManager::class);
        $packages->shouldReceive('catalog')->twice()->andReturn([
            ['slug' => 'nginx', 'installed' => true, 'service_active' => true, 'enabled_for_clients' => true, 'version' => '1.24'],
            ['slug' => 'apache', 'installed' => true, 'service_active' => true, 'enabled_for_clients' => false, 'version' => '2.4'],
        ]);
        $this->app->instance(NativePackageManager::class, $packages);

        $nginx = $this->payload($instance, 'engine-status', ['nginx']);
        $this->postJson(route('api.host-broker'), $nginx, ['X-XPanel-Signature' => $this->signature($nginx, $secret)])
            ->assertOk()->assertJson(['output' => "installed=true\nversion=1.24"]);

        $apache = $this->payload($instance, 'engine-status', ['apache']);
        $this->postJson(route('api.host-broker'), $apache, ['X-XPanel-Signature' => $this->signature($apache, $secret)])
            ->assertOk()->assertJson(['output' => "installed=false\nversion="]);

        $install = $this->payload($instance, 'engine-install', ['apache']);
        $this->postJson(route('api.host-broker'), $install, ['X-XPanel-Signature' => $this->signature($install, $secret)])
            ->assertStatus(422);
    }

    public function test_unapproved_backend_cannot_be_applied_even_if_instance_database_requests_it(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        (new PDO('sqlite:'.$instance->database_path))->exec("UPDATE sites SET web_server = 'openlitespeed' WHERE domain = 'example.test'");
        $siteRoot = '/home/'.$instance->system_user.'/public_html/example.test';
        $siteUser = 'xps'.substr(str_replace('-', '', $instance->uuid), 0, 6).'1'.substr(hash('sha256', 'example.test'), 0, 8);
        $payload = $this->payload($instance, 'apply', [
            'example.test', 'openlitespeed', 'php', '8.3', $siteRoot, $siteUser,
            $siteRoot.'/public', '-', '0', 'active', 'system', '-',
        ]);

        $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $this->signature($payload, $secret)])
            ->assertStatus(422);

        $restart = $this->payload($instance, 'site-restart', array_slice($payload['arguments'], 0, 6));
        $this->postJson(route('api.host-broker'), $restart, ['X-XPanel-Signature' => $this->signature($restart, $secret)])
            ->assertStatus(422);
    }

    public function test_installed_apache_cannot_be_withdrawn_while_a_managed_site_uses_it(): void
    {
        [$instance] = $this->instanceWithSite();
        (new PDO('sqlite:'.$instance->database_path))->exec("UPDATE sites SET web_server = 'apache' WHERE domain = 'example.test'");

        $this->assertTrue(app(NativePackageManager::class)->isWebServerInUse('apache'));
    }

    public function test_ownership_repairs_are_limited_to_the_instances_registered_site(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        $root = '/home/'.$instance->system_user.'/public_html/example.test';
        $siteUser = 'xps'.substr(str_replace('-', '', $instance->uuid), 0, 6).'1'.substr(hash('sha256', 'example.test'), 0, 8);
        foreach ([
            ['ownership-fix', ['example.test', $root, $siteUser], 200],
            ['ownership-sync-path', ['example.test', $root, $siteUser, $root.'/assets/file.txt'], 200],
            ['ownership-sync-tree', ['example.test', $root, $siteUser, $root.'/assets'], 200],
            ['ownership-sync-path', ['example.test', $root, $siteUser, '/home/'.$instance->system_user.'/other.txt'], 422],
            ['ownership-sync-path', ['example.test', $root, $siteUser, $root.'/../other.test/file.txt'], 422],
            ['ownership-fix', ['example.test', $root, 'xpsforeign123456789'], 422],
        ] as [$action, $arguments, $status]) {
            $payload = $this->payload($instance, $action, $arguments);
            $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $this->signature($payload, $secret)])
                ->assertStatus($status);
        }
    }

    public function test_access_removal_is_limited_to_the_instances_registered_site(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        $root = '/home/'.$instance->system_user.'/public_html/example.test';
        $siteUser = 'xps'.substr(str_replace('-', '', $instance->uuid), 0, 6).'1'.substr(hash('sha256', 'example.test'), 0, 8);
        foreach ([
            [[$siteUser, $root], 200],
            [[$siteUser, '/home/'.$instance->system_user.'/public_html/other.test'], 422],
            [[$siteUser, $root.'/../other.test'], 422],
            [['xpsforeign123456789', $root], 422],
        ] as [$arguments, $status]) {
            $payload = $this->payload($instance, 'access-remove', $arguments);
            $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $this->signature($payload, $secret)])
                ->assertStatus($status);
        }
    }

    private function instanceWithSite(): array
    {
        $uuid = '01234567-89ab-cdef-8123-456789abcdef';
        $secret = str_repeat('a', 64);
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Broker', 'domain' => 'broker.test', 'user_id' => $user->id, 'status' => 'active']);
        $account = HostingAccount::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Hosting broker',
            'status' => 'active',
        ]);
        $instanceRoot = str_replace('\\', '/', $this->root).'/'.$uuid;
        File::ensureDirectoryExists($instanceRoot.'/database');
        $database = $instanceRoot.'/database/database.sqlite';
        $pdo = new PDO('sqlite:'.$database);
        $pdo->exec('CREATE TABLE php_profiles (id INTEGER PRIMARY KEY, php_version TEXT, extensions TEXT)');
        $pdo->exec("CREATE TABLE sites (id INTEGER PRIMARY KEY, domain TEXT, web_server TEXT, type TEXT, php_version TEXT, php_profile_id INTEGER, document_root TEXT, system_user TEXT, public_path TEXT, node_version TEXT, runtime_port INTEGER, wildcard_domain INTEGER DEFAULT 0, status TEXT DEFAULT 'active')");
        $pdo->exec('CREATE TABLE site_databases (id INTEGER PRIMARY KEY, name TEXT, username TEXT)');
        $siteUser = 'xps'.substr(str_replace('-', '', $uuid), 0, 6).'1'.substr(hash('sha256', 'example.test'), 0, 8);
        $statement = $pdo->prepare('INSERT INTO sites (domain, web_server, type, php_version, document_root, system_user, public_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->execute(['example.test', 'nginx', 'php', '8.3', '/home/xhi0123456789ab/public_html/example.test', $siteUser, 'public']);

        $instance = HostInstance::create([
            'tenant_id' => $tenant->id, 'hosting_account_id' => $account->id,
            'uuid' => $uuid, 'panel_domain' => 'panel.broker.test',
            'access_port' => 10000,
            'system_user' => 'xhi0123456789ab', 'release_path' => '/opt/xpanel-host/releases/test',
            'instance_root' => $instanceRoot, 'database_path' => $database, 'broker_secret' => $secret,
            'php_version' => '8.3', 'status' => 'active',
        ]);

        return [$instance, $secret];
    }

    private function payload(HostInstance $instance, string $action, array $arguments): array
    {
        return [
            'instance_id' => $instance->uuid, 'request_id' => bin2hex(random_bytes(16)),
            'timestamp' => time(), 'action' => $action, 'arguments' => $arguments, 'input' => null,
        ];
    }

    private function signature(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $secret);
    }
}
