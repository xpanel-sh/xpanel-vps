<?php

namespace App\Services;

use App\Models\Site;

class NativeSiteProvisioner
{
    public function __construct(
        private NativeSiteConfigGenerator $configs,
        private ServerCommandRunner $commands,
    ) {}

    public function provision(Site $site): void
    {
        $site->ensureNativeRuntime();
        $this->configs->write($site);

        if ($this->appliesSystemChanges()) {
            $this->runHelper('apply', $site);
        }

        $site->forceFill([
            'status' => $site->status === 'suspended'
                ? 'suspended'
                : ($this->appliesSystemChanges() ? 'active' : 'staged'),
        ])->save();
    }

    public function reconfigure(Site $site): void
    {
        $this->configs->write($site);

        if ($this->appliesSystemChanges()) {
            $this->runHelper('apply', $site);
        }

        $site->forceFill([
            'status' => $site->status === 'suspended'
                ? 'suspended'
                : ($this->appliesSystemChanges() ? 'active' : 'staged'),
        ])->save();
    }

    public function restart(Site $site): void
    {
        if ($this->appliesSystemChanges()) {
            $this->runHelper('restart', $site);

            return;
        }

        $this->configs->write($site);
    }

    public function remove(Site $site): void
    {
        if ($this->appliesSystemChanges()) {
            $this->runHelper('remove', $site);
        }

        $this->configs->remove($site);
    }

    private function runHelper(string $action, Site $site): void
    {
        $this->commands->run([
            'sudo',
            '-n',
            (string) config('xpanel.native_hosting.site_helper'),
            $action,
            strtolower($site->domain),
            $site->web_server,
            $site->project_type,
            $site->php_version,
            $site->nativeDocumentRoot(),
            $site->systemUser(),
        ]);
    }

    private function appliesSystemChanges(): bool
    {
        return (bool) config('xpanel.native_hosting.apply_system_changes', false);
    }
}
