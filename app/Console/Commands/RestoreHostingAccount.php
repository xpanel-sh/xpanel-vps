<?php

namespace App\Console\Commands;

use App\Models\HostingAccount;
use App\Services\HostingAccountRestoreManager;
use Illuminate\Console\Command;
use Throwable;

class RestoreHostingAccount extends Command
{
    protected $signature = 'xpanel:hosting-restore {uuid}';

    protected $description = 'Restore a purged hosting account from its verified retention archive';

    public function handle(HostingAccountRestoreManager $restores): int
    {
        $account = HostingAccount::query()->where('uuid', (string) $this->argument('uuid'))->firstOrFail();
        try {
            $restores->restore($account);
            $this->info('Datos recuperados; el hosting permanece suspendido para revisión.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
