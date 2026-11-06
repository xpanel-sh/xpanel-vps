<?php

namespace App\Services;

use App\Models\HostingAccount;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class HostingAccountRestoreManager
{
    public function __construct(
        private readonly ServerCommandRunner $commands,
        private readonly HostingAccountArchiveManager $archives,
        private readonly HostInstanceProvisioner $instances,
        private readonly HostInstanceResourceLimiter $limits,
        private readonly AccountNotifier $notifier,
    ) {}

    public function confirmation(HostingAccount $account): string
    {
        return 'RESTAURAR '.strtoupper(substr(str_replace('-', '', $account->uuid), -8));
    }

    public function start(HostingAccount $account, string $confirmation): void
    {
        $account->loadMissing('hostInstance');
        $instance = $account->hostInstance;
        if (! hash_equals($this->confirmation($account), trim($confirmation))) {
            throw new RuntimeException('La frase de restauración no coincide.');
        }
        if (! in_array($account->status, ['purged', 'restore_failed'], true)
            || $account->purge_archive_status !== 'ready' || ! $instance
            || ! in_array($instance->status, ['purged', 'restore_failed'], true)) {
            throw new RuntimeException('La cuenta no está depurada o no conserva una copia integral válida.');
        }

        $this->archives->verify($account);
        [$projectId] = $this->limits->diskArguments($instance);
        $account->update(['status' => 'restoring', 'restore_error' => null, 'restore_requested_at' => now()]);
        $instance->update(['status' => 'restoring', 'last_error' => null]);
        try {
            $this->commands->run([
                'sudo', '-n', (string) config('xpanel.host_instances.helper'),
                'restore-start', $instance->uuid, (string) $projectId,
            ], timeout: 30);
        } catch (Throwable $exception) {
            $account->update(['status' => 'restore_failed', 'restore_error' => $exception->getMessage()]);
            $instance->update(['status' => 'restore_failed', 'last_error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function restore(HostingAccount $account): void
    {
        Cache::lock('hosting-restore:'.$account->uuid, 7200)->block(5, function () use ($account): void {
            $account->refresh()->loadMissing(['hostInstance', 'tenant']);
            $instance = $account->hostInstance;
            if ($account->status !== 'restoring' || ! $instance) {
                throw new RuntimeException('La cuenta no está esperando una restauración técnica.');
            }
            [$projectId] = $this->limits->diskArguments($instance);

            try {
                $output = $this->commands->run([
                    'sudo', '-n', (string) config('xpanel.host_instances.helper'),
                    'restore', $instance->uuid, $instance->system_user, (string) $projectId,
                    (string) $account->purge_archive_path, (string) $account->purge_archive_sha256,
                ], timeout: 7200);
                if (! str_contains($output, 'restored='.$instance->uuid)) {
                    throw new RuntimeException('El helper no confirmó la extracción completa de la instancia.');
                }

                $this->instances->apply($instance->fresh(), restartMode: 'immediate');
                $syncOutput = $this->commands->run([
                    'sudo', '-n', (string) config('xpanel.host_instances.helper'),
                    'restore-sync', $instance->uuid, $instance->system_user, $instance->release_path, $instance->php_version,
                ], timeout: 3600);
                if (! str_contains($syncOutput, 'synchronized='.$instance->uuid)) {
                    throw new RuntimeException('El helper no confirmó la resincronización completa de la instancia.');
                }
                $this->instances->setSuspended($instance->fresh(), true);
                $completeOutput = $this->commands->run([
                    'sudo', '-n', (string) config('xpanel.host_instances.helper'),
                    'restore-complete', $instance->uuid, (string) $account->purge_archive_sha256,
                ], timeout: 30);
                if (! str_contains($completeOutput, 'completed='.$instance->uuid)) {
                    throw new RuntimeException('El helper no confirmó el cierre seguro de la recuperación.');
                }
                $account->update([
                    'status' => 'suspended', 'restore_error' => null, 'restored_at' => now(),
                    'purge_after' => null, 'suspended_at' => now(),
                ]);
                $this->notifier->send(
                    $account->tenant, 'hosting.recovered', 'Datos del hosting recuperados',
                    "Se recuperó {$account->name} desde su copia integral. Permanece suspendido hasta la revisión y reactivación administrativa.",
                    $account, route('client.host.account', $account, false),
                );
            } catch (Throwable $exception) {
                $account->update(['status' => 'restore_failed', 'restore_error' => $exception->getMessage()]);
                $instance->update(['status' => 'restore_failed', 'last_error' => $exception->getMessage()]);
                throw $exception;
            }
        });
    }
}
