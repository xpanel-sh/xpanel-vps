<?php

namespace App\Console\Commands;

use App\Models\HostInstance;
use App\Services\HostUpdateCoordinator;
use Illuminate\Console\Command;

class UpdateHostInstance extends Command
{
    protected $signature = 'xpanel:host-update {uuid}';

    protected $description = 'Prepare the latest XPanel Host release and update one pinned instance';

    public function handle(HostUpdateCoordinator $updates): int
    {
        $instance = HostInstance::query()->where('uuid', $this->argument('uuid'))->firstOrFail();
        $updates->perform($instance);
        $this->info($instance->fresh()->update_status);

        return self::SUCCESS;
    }
}
