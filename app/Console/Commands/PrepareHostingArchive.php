<?php

namespace App\Console\Commands;

use App\Models\HostingAccount;
use App\Services\HostingAccountArchiveManager;
use Illuminate\Console\Command;
use Throwable;

class PrepareHostingArchive extends Command
{
    protected $signature = 'xpanel:hosting-archive {uuid}';
    protected $description = 'Create and verify a retention archive before a hosting account can be purged';

    public function handle(HostingAccountArchiveManager $archives): int
    {
        $account = HostingAccount::query()->where('uuid', (string) $this->argument('uuid'))->firstOrFail();

        try {
            $archives->prepare($account);
            $this->info('Copia de retención preparada y verificada.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
