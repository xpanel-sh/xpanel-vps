<?php

namespace App\Services;

use App\Models\HostingAccount;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class HostingAccountArchiveManager
{
    public function __construct(private readonly ServerCommandRunner $commands) {}

    public function start(HostingAccount $account): void
    {
        $account->loadMissing('hostInstance');
        $instance = $account->hostInstance;

        if ($account->status !== 'purge_pending' || ! $instance || $instance->status !== 'suspended') {
            throw new RuntimeException('Solo puede archivarse una cuenta con retención vencida y su instancia suspendida.');
        }
        if ($account->purge_archive_status === 'ready') {
            throw new RuntimeException('La copia de retención ya está preparada y verificada.');
        }
        if ($account->purge_archive_status === 'preparing'
            && $account->purge_archive_requested_at?->isAfter(now()->subHours(2))) {
            throw new RuntimeException('La copia de retención ya se está preparando.');
        }

        $account->update([
            'purge_archive_status' => 'preparing',
            'purge_archive_error' => null,
            'purge_archive_requested_at' => now(),
        ]);

        try {
            $this->commands->run([
                'sudo', '-n', (string) config('xpanel.host_instances.helper'),
                'archive-start', $instance->uuid,
            ], timeout: 30);
        } catch (Throwable $exception) {
            $account->update(['purge_archive_status' => 'failed', 'purge_archive_error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function prepare(HostingAccount $account): void
    {
        Cache::lock('hosting-retention-archive:'.$account->uuid, 7200)->block(5, function () use ($account): void {
            $account->refresh()->loadMissing('hostInstance');
            $instance = $account->hostInstance;
            if ($account->status !== 'purge_pending' || ! $instance || $instance->status !== 'suspended') {
                throw new RuntimeException('La cuenta dejó de ser apta para generar la copia de retención.');
            }

            try {
                $output = $this->commands->run([
                    'sudo', '-n', (string) config('xpanel.host_instances.helper'),
                    'archive', $instance->uuid, $instance->system_user,
                ], timeout: 7200);
                $result = $this->parse($output, $instance->uuid);
                $account->update([
                    'purge_archive_status' => 'ready',
                    'purge_archive_path' => $result['archive_path'],
                    'purge_archive_sha256' => $result['archive_sha256'],
                    'purge_archive_error' => null,
                    'purge_archived_at' => now(),
                    'purge_archive_verified_at' => now(),
                ]);
            } catch (Throwable $exception) {
                $account->update(['purge_archive_status' => 'failed', 'purge_archive_error' => $exception->getMessage()]);
                throw $exception;
            }
        });
    }

    public function verify(HostingAccount $account): void
    {
        $account->loadMissing('hostInstance');
        $instance = $account->hostInstance;
        if (! in_array($account->status, ['purge_pending', 'purging', 'purged', 'restore_failed', 'restoring'], true)
            || $account->purge_archive_status !== 'ready' || ! $instance) {
            throw new RuntimeException('La cuenta no tiene una copia de retención preparada para verificar.');
        }
        $archive = $this->parse("archive_path={$account->purge_archive_path}\narchive_sha256={$account->purge_archive_sha256}", $instance->uuid);

        try {
            $output = $this->commands->run([
                'sudo', '-n', (string) config('xpanel.host_instances.helper'),
                'archive-verify', $instance->uuid, $archive['archive_path'], $archive['archive_sha256'],
            ], timeout: 7200);
            if (! str_contains($output, 'verified='.$archive['archive_sha256'])) {
                throw new RuntimeException('El helper no confirmó la integridad completa del archivo.');
            }
            $account->update(['purge_archive_verified_at' => now(), 'purge_archive_error' => null]);
        } catch (Throwable $exception) {
            $account->update([
                'purge_archive_status' => 'failed',
                'purge_archive_verified_at' => null,
                'purge_archive_error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    /** @return array{archive_path:string,archive_sha256:string} */
    private function parse(string $output, string $uuid): array
    {
        $values = [];
        foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $values[trim($key)] = trim($value);
            }
        }

        $path = $values['archive_path'] ?? '';
        $sha = strtolower($values['archive_sha256'] ?? '');
        $prefix = '/var/backups/xpanel/hosting-retention/'.$uuid.'/';
        if (! str_starts_with($path, $prefix)
            || ! preg_match('/^[0-9]{8}T[0-9]{6}Z\.tar\.gz$/D', basename($path))
            || ! preg_match('/^[a-f0-9]{64}$/D', $sha)) {
            throw new RuntimeException('El helper no devolvió una copia de retención verificable.');
        }

        return ['archive_path' => $path, 'archive_sha256' => $sha];
    }
}
