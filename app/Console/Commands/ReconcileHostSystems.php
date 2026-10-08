<?php

namespace App\Console\Commands;

use App\Models\HostInstance;
use App\Services\HostInstanceConfigGenerator;
use App\Services\HostInstanceProvisioner;
use App\Services\ServerCommandRunner;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class ReconcileHostSystems extends Command
{
    protected $signature = 'xpanel:system-reconcile {--repair : Reapply safe system configuration to existing managed Host instances}';

    protected $description = 'Verify managed Host prerequisites and reconcile existing instances after a VPS update';

    public function handle(HostInstanceProvisioner $provisioner, HostInstanceConfigGenerator $generator, ServerCommandRunner $commands): int
    {
        if (! config('xpanel.native_hosting.apply_system_changes')) {
            $this->warn('Sin cambios de sistema: verificación omitida en este entorno.');

            return self::SUCCESS;
        }

        $failed = 0;
        $instances = HostInstance::query()->where('status', 'active')->whereNotNull('provisioned_at')->get();
        foreach ($instances as $instance) {
            try {
                $instance->loadMissing(['hostingAccount.plan', 'tenant.plan']);
                $plan = $instance->hostingAccount?->plan ?? $instance->tenant?->plan;
                $provisioner->assertPlanReady($plan);
                $stagedEnvironment = rtrim((string) config('xpanel.host_instances.staging_root'), '/\\')
                    .DIRECTORY_SEPARATOR.$instance->uuid.DIRECTORY_SEPARATOR.'instance.env';
                if (! is_file($stagedEnvironment) || is_link($stagedEnvironment)) {
                    throw new RuntimeException('Falta el entorno original de la instancia; no se regenerará su APP_KEY automáticamente.');
                }
                $staged = $generator->generate($instance);
                $check = [
                    'sudo', '-n', (string) config('xpanel.host_instances.helper'), 'verify-instance',
                    $instance->uuid, $instance->system_user, (string) (100000 + $instance->id), $staged['directory'],
                ];
                try {
                    $commands->run($check);
                    $this->line("OK {$instance->uuid}: sistema al día.");
                } catch (Throwable $exception) {
                    if (! $this->option('repair')) {
                        throw $exception;
                    }
                    $this->warn("{$instance->uuid}: {$exception->getMessage()} — reaplicando configuración.");
                    // The pinned Host release remains unchanged. Repair only
                    // this instance, then verify the resulting live services.
                    $provisioner->apply($instance, null, 'immediate');
                    $commands->run($check);
                    $this->line("OK {$instance->uuid}: reparada y verificada.");
                }
            } catch (Throwable $exception) {
                $failed++;
                $this->error("{$instance->uuid}: {$exception->getMessage()}");
            }
        }

        $this->info("Instancias activas verificadas: {$instances->count()}; pendientes: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
