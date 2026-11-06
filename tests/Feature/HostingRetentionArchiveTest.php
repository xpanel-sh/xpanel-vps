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
        $starter->shouldReceive('run')->once()->withArgs(fn (array $command, mixed $input, int $timeout) => $command[2] === config('xpanel.host_instances.helper')
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
        $worker->shouldReceive('run')->once()->withArgs(fn (array $command, mixed $input, int $timeout) => $command[3] === 'archive'
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

        $account->update(['purge_archive_verified_at' => null]);
        $verifier = Mockery::mock(ServerCommandRunner::class);
        $verifier->shouldReceive('run')->once()->withArgs(fn (array $command, mixed $input, int $timeout) => $command[3] === 'archive-verify'
            && $command[4] === $instance->uuid
            && $command[5] === $archive
            && $command[6] === $sha
            && $timeout === 7200
        )->andReturn('verified='.$sha);
        $this->app->instance(ServerCommandRunner::class, $verifier);

        $this->actingAs($admin, 'admin')->post(route('admin.instances.archive.verify', $instance))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($account->fresh()->purge_archive_verified_at);
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

    public function test_verified_archive_is_required_before_background_purge(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.host_instances.release_path', '/opt/xpanel-host/current');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Cliente', 'domain' => 'client.test', 'user_id' => $owner->id, 'status' => 'active']);
        $account = HostingAccount::create(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'name' => 'Expirado', 'status' => 'purge_pending']);
        $instance = app(HostInstanceProvisioner::class)->create($account);
        $instance->update(['status' => 'suspended']);
        $archive = '/var/backups/xpanel/hosting-retention/'.$instance->uuid.'/20261106T120000Z.tar.gz';
        $sha = str_repeat('b', 64);
        $account->update([
            'purge_archive_status' => 'ready', 'purge_archive_path' => $archive,
            'purge_archive_sha256' => $sha, 'purge_archived_at' => now(), 'purge_archive_verified_at' => now(),
        ]);

        $this->mock(ServerCommandRunner::class)->shouldNotReceive('run');
        $this->actingAs($admin, 'admin')->post(route('admin.instances.purge', $instance), ['confirmation' => 'INCORRECTA'])
            ->assertRedirect()->assertSessionHasErrors('purge');
        $this->assertSame('purge_pending', $account->fresh()->status);

        $runner = Mockery::mock(ServerCommandRunner::class);
        $runner->shouldReceive('run')->once()->ordered()->withArgs(fn (array $command) => $command[3] === 'archive-verify')->andReturn('verified='.$sha);
        $runner->shouldReceive('run')->once()->ordered()->withArgs(fn (array $command) => $command[3] === 'purge-start' && $command[4] === $instance->uuid)->andReturn('started=xpanel-hosting-purge');
        $this->app->instance(ServerCommandRunner::class, $runner);
        $phrase = 'ELIMINAR '.strtoupper(substr(str_replace('-', '', $account->uuid), -8));

        $this->actingAs($admin, 'admin')->post(route('admin.instances.purge', $instance), ['confirmation' => $phrase])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('purging', $account->fresh()->status);
        $this->assertSame('purging', $instance->fresh()->status);

        $worker = Mockery::mock(ServerCommandRunner::class);
        $worker->shouldReceive('run')->once()->withArgs(fn (array $command, mixed $input, int $timeout) => $command[3] === 'purge' && $command[4] === $instance->uuid
            && $command[7] === $archive && $command[8] === $sha && $timeout === 7200
        )->andReturn('purged='.$instance->uuid);
        $this->app->instance(ServerCommandRunner::class, $worker);

        $this->artisan('xpanel:hosting-purge', ['uuid' => $account->uuid])->assertSuccessful();
        $this->assertSame('purged', $account->fresh()->status);
        $this->assertNotNull($account->fresh()->purged_at);
        $this->assertSame('purged', $instance->fresh()->status);
        $this->assertNull($instance->fresh()->broker_secret);
    }

    public function test_admin_can_restore_a_purged_account_and_worker_leaves_it_suspended(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        config()->set('xpanel.host_instances.release_path', '/opt/xpanel-host/current');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Cliente', 'domain' => 'client.test', 'user_id' => $owner->id, 'status' => 'active']);
        $account = HostingAccount::create(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'name' => 'Depurado', 'status' => 'purged']);
        $instance = app(HostInstanceProvisioner::class)->create($account);
        $archive = '/var/backups/xpanel/hosting-retention/'.$instance->uuid.'/20261106T160000Z.tar.gz';
        $sha = str_repeat('c', 64);
        $account->update([
            'status' => 'purged', 'purge_archive_status' => 'ready', 'purge_archive_path' => $archive,
            'purge_archive_sha256' => $sha, 'purge_archived_at' => now(), 'purge_archive_verified_at' => now(),
            'purged_at' => now(),
        ]);
        $instance->forceFill(['status' => 'purged', 'broker_secret' => null, 'initial_password' => null])->save();

        $starter = Mockery::mock(ServerCommandRunner::class);
        $starter->shouldReceive('run')->once()->ordered()
            ->withArgs(fn (array $command) => $command[3] === 'archive-verify' && $command[4] === $instance->uuid)
            ->andReturn('verified='.$sha);
        $starter->shouldReceive('run')->once()->ordered()
            ->withArgs(fn (array $command, mixed $input, int $timeout) => $command[3] === 'restore-start'
                && $command[4] === $instance->uuid && $timeout === 30)
            ->andReturn('started=xpanel-hosting-restore');
        $this->app->instance(ServerCommandRunner::class, $starter);
        $phrase = 'RESTAURAR '.strtoupper(substr(str_replace('-', '', $account->uuid), -8));

        $this->actingAs($admin, 'admin')->post(route('admin.instances.archive.restore', $instance), ['confirmation' => $phrase])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('restoring', $account->fresh()->status);
        $this->assertSame('restoring', $instance->fresh()->status);

        $worker = Mockery::mock(ServerCommandRunner::class);
        $worker->shouldReceive('run')->once()->ordered()->withArgs(fn (array $command, mixed $input, int $timeout) => $command[3] === 'restore' && $command[4] === $instance->uuid
            && $command[7] === $archive && $command[8] === $sha && $timeout === 7200
        )->andReturn('restored='.$instance->uuid);
        $worker->shouldReceive('run')->once()->ordered()->withArgs(fn (array $command, mixed $input, int $timeout) => $command[3] === 'restore-sync' && $command[4] === $instance->uuid && $timeout === 3600
        )->andReturn('synchronized='.$instance->uuid);
        $worker->shouldReceive('run')->once()->ordered()->withArgs(fn (array $command, mixed $input, int $timeout) => $command[3] === 'restore-complete' && $command[4] === $instance->uuid
            && $command[5] === $sha && $timeout === 30
        )->andReturn('completed='.$instance->uuid);
        $this->app->instance(ServerCommandRunner::class, $worker);

        $this->artisan('xpanel:hosting-restore', ['uuid' => $account->uuid])->assertSuccessful();
        $this->assertSame('suspended', $account->fresh()->status);
        $this->assertNotNull($account->fresh()->restored_at);
        $this->assertNull($account->fresh()->purge_after);
        $this->assertSame('suspended', $instance->fresh()->status);
        $this->assertNotNull($instance->fresh()->broker_secret);
        $this->assertDatabaseHas('account_notifications', [
            'hosting_account_id' => $account->id, 'type' => 'hosting.recovered',
        ]);
    }
}
