<?php

namespace App\Services;

class HostReleaseManager
{
    public function __construct(private readonly ServerCommandRunner $commands) {}

    public function prepareLatest(): string
    {
        return trim($this->commands->run([
            'sudo', '-n', (string) config('xpanel.host_instances.helper'), 'host-release-prepare',
        ], null, 1800));
    }

    public function preparedRevision(): ?string
    {
        $path = realpath('/opt/xpanel-host/current');

        return $path && preg_match('#^/opt/xpanel-host/releases/([a-f0-9]{12})$#', $path)
            ? basename($path)
            : null;
    }
}
