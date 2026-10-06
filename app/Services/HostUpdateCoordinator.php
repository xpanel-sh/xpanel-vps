<?php

namespace App\Services;

use App\Models\HostInstance;
use RuntimeException;
use Throwable;

class HostUpdateCoordinator
{
    public function __construct(
        private readonly ServerCommandRunner $commands,
        private readonly HostReleaseManager $releases,
        private readonly HostInstanceUpdater $updater,
    ) {}

    public function start(HostInstance $instance): void
    {
        if ($instance->status !== 'active' || ! config('xpanel.native_hosting.apply_system_changes')) {
            throw new RuntimeException('La instancia debe estar activa para actualizar Host.');
        }

        $claimed = HostInstance::query()->whereKey($instance->id)
            ->where(fn ($query) => $query->whereNull('update_status')
                ->orWhereNotIn('update_status', ['pending', 'running'])
                ->orWhere('update_started_at', '<', now()->subHours(2)))
            ->update([
                'update_status' => 'pending',
                'update_error' => null,
                'update_started_at' => now(),
                'update_finished_at' => null,
            ]);
        if (! $claimed) {
            throw new RuntimeException('Ya hay una actualización en curso para esta cuenta.');
        }

        try {
            $this->commands->run([
                'sudo', '-n', (string) config('xpanel.host_instances.helper'), 'host-update-start', $instance->uuid,
            ]);
        } catch (Throwable $exception) {
            $instance->update(['update_status' => 'failed', 'update_error' => $exception->getMessage(), 'update_finished_at' => now()]);
            throw $exception;
        }
    }

    public function perform(HostInstance $instance): void
    {
        if ($instance->update_status !== 'pending') {
            throw new RuntimeException('Esta instancia no tiene una actualización pendiente.');
        }
        $instance->update(['update_status' => 'running']);
        try {
            $this->releases->prepareLatest();
            $updated = $this->updater->updateToCurrent($instance->fresh());
            $instance->update([
                'update_status' => $updated ? 'completed' : 'unchanged',
                'update_error' => null,
                'update_finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $instance->update([
                'update_status' => 'failed',
                'update_error' => $exception->getMessage(),
                'update_finished_at' => now(),
            ]);
            throw $exception;
        }
    }

    public function status(HostInstance $instance): array
    {
        if (in_array($instance->update_status, ['pending', 'running'], true)
            && $instance->update_started_at?->lt(now()->subHours(2))) {
            HostInstance::query()->whereKey($instance->id)
                ->whereIn('update_status', ['pending', 'running'])
                ->where('update_started_at', '<', now()->subHours(2))
                ->update([
                    'update_status' => 'failed',
                    'update_error' => 'La actualización excedió dos horas. Revisa el servicio y vuelve a intentarlo.',
                    'update_finished_at' => now(),
                ]);
            $instance->refresh();
        }

        return ['status' => $instance->update_status, 'error' => $instance->update_error];
    }
}
