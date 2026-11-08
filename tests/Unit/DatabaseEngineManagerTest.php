<?php

namespace Tests\Unit;

use App\Services\DatabaseEngineManager;
use App\Services\ServerCommandRunner;
use Mockery;
use Tests\TestCase;

class DatabaseEngineManagerTest extends TestCase
{
    public function test_dbgate_status_includes_installed_and_enabled_state(): void
    {
        $runner = Mockery::mock(ServerCommandRunner::class);
        $runner->shouldReceive('run')->times(4)->andReturn("installed=true\nenabled=true\nversion=1.0");

        $status = (new DatabaseEngineManager($runner))->statuses()['dbgate'];

        $this->assertTrue($status['installed']);
        $this->assertTrue($status['enabled']);
        $this->assertSame('1.0', $status['version']);
    }

    public function test_dbgate_can_be_disabled_for_all_host_instances(): void
    {
        $runner = Mockery::mock(ServerCommandRunner::class);
        $runner->shouldReceive('run')->once()->with([
            'sudo', '-n', '/opt/xpanel-vps/scripts/xpanel-instance-helper.sh', 'dbgate-set-enabled', 'false',
        ], null, 120)->andReturn('DbGate disabled.');

        (new DatabaseEngineManager($runner))->setDbGateEnabled(false);
        $this->addToAssertionCount(1);
    }
}
