<?php

namespace App\Services;

use App\Models\HostInstance;
use RuntimeException;
use Throwable;

class HostInstanceUpdater
{
    public function __construct(private readonly HostInstanceProvisioner $provisioner) {}

    public function updateToCurrent(HostInstance $instance): bool
    {
        $target = realpath('/opt/xpanel-host/current');
        if ($target === false
            || ! preg_match('#^/opt/xpanel-host/releases/[A-Za-z0-9._-]+$#', $target)
            || ! is_file($target.'/artisan')
            || ! is_file($target.'/public/index.php')) {
            throw new RuntimeException('No hay una release actual de XPanel Host preparada para esta instancia. Intenta preparar la versión desde Actualizaciones.');
        }

        $target = str_replace('\\', '/', $target);
        if (rtrim($instance->release_path, '/') === $target) {
            // A new VPS helper may need to repair an existing instance even
            // when the Host release itself has not changed.
            $this->provisioner->apply($instance->fresh());

            return false;
        }

        $previous = [
            'release_path' => $instance->release_path,
            'version' => $instance->version,
            'status' => $instance->status,
        ];

        try {
            $instance->update([
                'release_path' => $target,
                'version' => basename($target),
            ]);
            $this->provisioner->apply($instance->fresh());
        } catch (Throwable $exception) {
            $instance->update($previous);
            try {
                $this->provisioner->apply($instance->fresh());
            } catch (Throwable) {
                // Preserve the original update error; the instance already records
                // the rollback failure through the provisioner.
            }
            throw $exception;
        }

        return true;
    }
}
