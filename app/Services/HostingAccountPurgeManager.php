<?php

namespace App\Services;

use App\Models\HostingAccount;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class HostingAccountPurgeManager
{
    public function __construct(
        private readonly ServerCommandRunner $commands,
        private readonly HostingAccountArchiveManager $archives,
        private readonly HostInstanceResourceLimiter $limits,
    ) {}

    public function confirmation(HostingAccount $account): string
    {
        return 'ELIMINAR '.strtoupper(substr(str_replace('-', '', $account->uuid), -8));
    }

    public function start(HostingAccount $account, string $confirmation): void
    {
        $account->loadMissing('hostInstance');
        $instance = $account->hostInstance;
        if (! hash_equals($this->confirmation($account), trim($confirmation))) {
            throw new RuntimeException('La frase de confirmación no coincide.');
        }
        if (! in_array($account->status, ['purge_pending', 'purge_failed'], true)
            || $account->purge_archive_status !== 'ready'
            || ! $instance
            || ! in_array($instance->status, ['suspended', 'purge_failed'], true)) {
            throw new RuntimeException('La cuenta debe estar suspendida, con retención vencida y una copia integral lista.');
        }

        // Never trust an old database flag for a destructive operation. Read
        // and validate the root-owned archive again immediately before queueing.
        $this->archives->verify($account);
        [$projectId] = $this->limits->diskArguments($instance);
        $account->update(['status' => 'purging', 'purge_error' => null, 'purge_requested_at' => now()]);
        $instance->update(['status' => 'purging', 'last_error' => null]);

        try {
            $this->commands->run([
                'sudo', '-n', (string) config('xpanel.host_instances.helper'),
                'purge-start', $instance->uuid, (string) $projectId,
            ], timeout: 30);
        } catch (Throwable $exception) {
            $account->update(['status' => 'purge_pending', 'purge_error' => $exception->getMessage()]);
            $instance->update(['status' => 'suspended', 'last_error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function purge(HostingAccount $account): void
    {
        Cache::lock('hosting-purge:'.$account->uuid, 7200)->block(5, function () use ($account): void {
            $account->refresh()->loadMissing('hostInstance');
            $instance = $account->hostInstance;
            if ($account->status !== 'purging' || ! $instance) {
                throw new RuntimeException('La cuenta no está esperando una depuración técnica.');
            }
            [$projectId] = $this->limits->diskArguments($instance);

            try {
                $output = $this->commands->run([
                    'sudo', '-n', (string) config('xpanel.host_instances.helper'),
                    'purge', $instance->uuid, $instance->system_user, (string) $projectId,
                    (string) $account->purge_archive_path, (string) $account->purge_archive_sha256,
                ], timeout: 7200);
                if (! str_contains($output, 'purged='.$instance->uuid)) {
                    throw new RuntimeException('El helper no confirmó la depuración completa de la instancia.');
                }

                $account->update(['status' => 'purged', 'purge_error' => null, 'purged_at' => now()]);
                $instance->forceFill([
                    'status' => 'purged', 'last_error' => null,
                    'broker_secret' => null, 'initial_password' => null,
                ])->save();
            } catch (Throwable $exception) {
                $account->update(['status' => 'purge_failed', 'purge_error' => $exception->getMessage()]);
                $instance->update(['status' => 'purge_failed', 'last_error' => $exception->getMessage()]);
                throw $exception;
            }
        });
    }
}
