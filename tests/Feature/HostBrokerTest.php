<?php

namespace Tests\Feature;

use App\Models\HostBrokerOperation;
use App\Models\HostInstance;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PDO;
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

    public function test_a_signed_instance_can_stage_an_authorized_site_operation(): void
    {
        [$instance, $secret] = $this->instanceWithSite();
        $siteRoot = '/home/'.$instance->system_user.'/public_html/example.test';
        $arguments = [
            'example.test', 'nginx', 'php', '8.3',
            $siteRoot,
            'xps'.substr(str_replace('-', '', $instance->uuid), 0, 6).'1'.substr(hash('sha256', 'example.test'), 0, 8),
            $siteRoot.'/public',
            '-', '0', 'active',
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
            $siteRoot.'/public', '22', '32123', 'active',
        ]);

        $this->postJson(route('api.host-broker'), $payload, ['X-XPanel-Signature' => $this->signature($payload, $secret)])
            ->assertOk();

        $this->assertDatabaseHas('host_broker_resources', ['type' => 'runtime-port', 'name' => '32123']);
        $this->assertDatabaseHas('host_broker_resources', ['type' => 'site-domain', 'name' => '*.example.test']);
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

    private function instanceWithSite(): array
    {
        $uuid = '01234567-89ab-cdef-8123-456789abcdef';
        $secret = str_repeat('a', 64);
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Broker', 'domain' => 'broker.test', 'user_id' => $user->id, 'status' => 'active']);
        $instanceRoot = str_replace('\\', '/', $this->root).'/'.$uuid;
        File::ensureDirectoryExists($instanceRoot.'/database');
        $database = $instanceRoot.'/database/database.sqlite';
        $pdo = new PDO('sqlite:'.$database);
        $pdo->exec("CREATE TABLE sites (id INTEGER PRIMARY KEY, domain TEXT, web_server TEXT, type TEXT, php_version TEXT, document_root TEXT, system_user TEXT, public_path TEXT, node_version TEXT, runtime_port INTEGER, wildcard_domain INTEGER DEFAULT 0, status TEXT DEFAULT 'active')");
        $pdo->exec('CREATE TABLE site_databases (id INTEGER PRIMARY KEY, name TEXT, username TEXT)');
        $siteUser = 'xps'.substr(str_replace('-', '', $uuid), 0, 6).'1'.substr(hash('sha256', 'example.test'), 0, 8);
        $statement = $pdo->prepare('INSERT INTO sites (domain, web_server, type, php_version, document_root, system_user, public_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->execute(['example.test', 'nginx', 'php', '8.3', '/home/xhi0123456789ab/public_html/example.test', $siteUser, 'public']);

        $instance = HostInstance::create([
            'tenant_id' => $tenant->id, 'uuid' => $uuid, 'panel_domain' => 'panel.broker.test',
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
