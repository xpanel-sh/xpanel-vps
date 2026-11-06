<?php

namespace App\Console\Commands;

use App\Models\HostInstance;
use App\Services\HostInstanceResourceLimiter;
use App\Services\ServerCommandRunner;
use Illuminate\Console\Command;

class ReconcileHostingQuotas extends Command
{
    protected $signature = 'xpanel:quotas-reconcile {--uuid=}';

    protected $description = 'Reconcile the combined hosting storage budget';

    public function handle(ServerCommandRunner $commands, HostInstanceResourceLimiter $limits): int
    {
        $query = HostInstance::query()->whereNotNull('provisioned_at')->whereIn('status', ['active', 'suspended']);
        if ($uuid = trim((string) $this->option('uuid'))) {
            $query->where('uuid', $uuid);
        }

        $failed = 0;
        foreach ($query->cursor() as $instance) {
            [$projectId, $storageMib, $inodes] = $limits->diskArguments($instance);
            if ($storageMib < 1 || $inodes < 1) {
                continue;
            }
            try {
                $commands->run([
                    'sudo', '-n', (string) config('xpanel.host_instances.helper'),
                    'reconcile-disk-quota', $instance->uuid, $instance->system_user, (string) $projectId,
                ], timeout: 300);
            } catch (\Throwable $exception) {
                $failed++;
                $this->error($instance->uuid.': '.$exception->getMessage());
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
