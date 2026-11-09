<?php

namespace App\Console\Commands;

use App\Models\HostingAccount;
use App\Services\InitialHostSiteProvisioner;
use Illuminate\Console\Command;

class ProvisionInitialHostSites extends Command
{
    protected $signature = 'xpanel:initial-sites';

    protected $description = 'Provision requested first websites after their Host accounts are ready';

    public function handle(InitialHostSiteProvisioner $sites): int
    {
        HostingAccount::query()->where('initial_site_status', 'pending')
            ->with('hostInstance')->orderBy('id')->limit(10)->get()
            ->each(fn (HostingAccount $account) => $sites->provision($account));
        return self::SUCCESS;
    }
}
