<?php

namespace Tests\Feature;

use App\Models\HostingAccount;
use App\Models\Tenant;
use App\Models\User;
use App\Services\HostInstanceProvisioner;
use App\Services\ServerCommandRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class HostingRetentionArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_starts_and_worker_verifies_a_retention_archive(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.host_instances.release_path', '/opt/xpanel-host/current');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Cliente', 'domain' => 'client.test', 'user_id' => $owner->id, 'status' => 'active']);
        $account = HostingAccount::create([
            'uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id,
            'name' => 'Hosting retenido', 'status' => 'purge_pending',
        ]);
        $instance = app(HostInstanceProvisioner::class)->create($account);
        $instance->update(['status' => 'suspended']);

        $starter = Mockery::mock(ServerCommandRunner::class);
        $starter->shouldReceive('run')->once()->withArgs(fn (array $command, mixed $input, int $timeout) =>
            $command[2] === config('xpanel.host_instances.helper')
            && $command[3] === 'archive-start'
            && $command[4] === $instance->uuid
            && $timeout === 30
        )->andReturn('started=xpanel-hosting-archive');
        $this->app->instance(ServerCommandRunner::class, $starter);

        $this->actingAs($admin, 'admin')->post(route('admin.instances.archive', $instance))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('preparing', $account->fresh()->purge_archive_status);

        $archive = '/var/backups/xpanel/hosting-retention/'.$instance->uuid.'/20261105T120000Z.tar.gz';
        $sha = str_repeat('a', 64);
        $worker = Mockery::mock(ServerCommandRunner::class);
        $worker->shouldReceive('run')->once()->withArgs(fn (array $command, mixed $input, int $timeout) =>
            $command[3] === 'archive'
            && $command[4] === $instance->uuid
            && $command[5] === $instance->system_user
            && $timeout === 7200
        )->andReturn("archive_path={$archive}\narchive_sha256={$sha}\ndatabase_count=2");
        $this->app->instance(ServerCommandRunner::class, $worker);

        $this->artisan('xpanel:hosting-archive', ['uuid' => $account->uuid])->assertSuccessful();

        $account->refresh();
        $this->assertSame('ready', $account->purge_archive_status);
        $this->assertSame($archive, $account->purge_archive_path);
        $this->assertSame($sha, $account->purge_archive_sha256);
        $this->assertNotNull($account->purge_archived_at);
        $this->assertDatabaseHas('host_instances', ['id' => $instance->id]);
    }

    public function test_active_hosting_cannot_start_a_retention_archive(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.host_instances.release_path', '/opt/xpanel-host/current');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Cliente', 'domain' => 'client.test', 'user_id' => $owner->id, 'status' => 'active']);
        $account = HostingAccount::create(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'name' => 'Activo', 'status' => 'active']);
        $instance = app(HostInstanceProvisioner::class)->create($account);
        $instance->update(['status' => 'active']);

        $this->mock(ServerCommandRunner::class)->shouldNotReceive('run');
        $this->actingAs($admin, 'admin')->post(route('admin.instances.archive', $instance))
            ->assertRedirect()->assertSessionHasErrors('archive');

        $this->assertNull($account->fresh()->purge_archive_status);
    }
}
