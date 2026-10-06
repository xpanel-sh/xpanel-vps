<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class HostReleaseManager
{
    public function __construct(private readonly ServerCommandRunner $commands) {}

    public function prepareLatest(?callable $onStage = null): string
    {
        $buffer = '';
        $output = $this->commands->run([
            'sudo', '-n', (string) config('xpanel.host_instances.helper'), 'host-release-prepare',
        ], null, 1800, function (string $type, string $chunk) use (&$buffer, $onStage): void {
            if ($type !== Process::OUT || $onStage === null) {
                return;
            }
            $buffer .= $chunk;
            while (($end = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $end);
                $buffer = substr($buffer, $end + 1);
                if (preg_match('/^XPANEL_STAGE:(download|php|javascript|build|ready)$/', trim($line), $match)) {
                    $onStage($match[1]);
                }
            }
        });

        if (! preg_match('/(?:^|\n)([a-f0-9]{12})\s*$/', $output, $match)) {
            throw new RuntimeException('La preparación de Host no devolvió una revisión válida.');
        }

        return $match[1];
    }

    public function preparedRevision(): ?string
    {
        $path = realpath('/opt/xpanel-host/current');

        return $path && preg_match('#^/opt/xpanel-host/releases/([a-f0-9]{12})$#', $path)
            ? basename($path)
            : null;
    }
}
