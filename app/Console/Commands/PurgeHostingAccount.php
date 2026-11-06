<?php

namespace App\Console\Commands;

use App\Models\HostingAccount;
use App\Services\HostingAccountPurgeManager;
use Illuminate\Console\Command;
use Throwable;

class PurgeHostingAccount extends Command
{
    protected $signature = 'xpanel:hosting-purge {uuid}';

    protected $description = 'Purge verified hosting resources while preserving its retention archive and commercial record';

    public function handle(HostingAccountPurgeManager $purges): int
    {
        $account = HostingAccount::query()->where('uuid', (string) $this->argument('uuid'))->firstOrFail();
        try {
            $purges->purge($account);
            $this->info('Recursos técnicos depurados; el archivo y el registro comercial permanecen.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
