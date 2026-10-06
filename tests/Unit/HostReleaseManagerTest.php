<?php

namespace Tests\Unit;

use App\Services\HostReleaseManager;
use App\Services\ServerCommandRunner;
use Mockery;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class HostReleaseManagerTest extends TestCase
{
    public function test_preparation_reports_streamed_stages_and_returns_only_revision(): void
    {
        $runner = Mockery::mock(ServerCommandRunner::class);
        $runner->shouldReceive('run')->once()->withArgs(function ($command, $input, $timeout, $callback): bool {
            $this->assertSame('host-release-prepare', $command[3]);
            $this->assertNull($input);
            $this->assertSame(1800, $timeout);
            $callback(Process::OUT, 'XPANEL_STAGE:down');
            $callback(Process::OUT, "load\nXPANEL_STAGE:php\n");

            return true;
        })->andReturn("XPANEL_STAGE:download\nXPANEL_STAGE:php\n2597ed78245e\n");

        $stages = [];
        $revision = (new HostReleaseManager($runner))->prepareLatest(function (string $stage) use (&$stages): void {
            $stages[] = $stage;
        });

        $this->assertSame('2597ed78245e', $revision);
        $this->assertSame(['download', 'php'], $stages);
    }

    public function test_release_cleanup_does_not_turn_a_successful_revision_into_a_failure(): void
    {
        $script = file_get_contents(base_path('scripts/prepare-host-release.sh'));

        $this->assertStringContainsString("  return 0\n}\ntrap cleanup EXIT", $script);
    }
}
