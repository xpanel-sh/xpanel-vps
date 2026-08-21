<?php

namespace App\Console\Commands;

use App\Models\HostInstance;
use App\Models\Tenant;
use App\Services\HostInstanceProvisioner;
use Illuminate\Console\Command;

class ManageHostInstances extends Command
{
    protected $signature = 'xpanel:instances
        {action=list : list, create, apply, suspend or resume}
        {instance? : Instance UUID, or tenant ID for create}
        {--domain= : Panel domain when creating}
        {--password-stdin : Read the initial owner password from standard input}';

    protected $description = 'Manage isolated XPanel Host instances from the VPS control plane';

    public function handle(HostInstanceProvisioner $provisioner): int
    {
        $action = strtolower((string) $this->argument('action'));
        if ($action === 'list') {
            $this->table(
                ['UUID', 'Cliente', 'Dominio', 'Versión', 'PHP', 'Estado'],
                HostInstance::with('tenant')->orderBy('id')->get()->map(fn (HostInstance $instance) => [
                    $instance->uuid, $instance->tenant->name, $instance->panel_domain,
                    $instance->version, $instance->php_version, $instance->status,
                ])->all(),
            );

            return self::SUCCESS;
        }

        if ($action === 'create') {
            $tenant = Tenant::query()->findOrFail((int) $this->argument('instance'));
            if ($tenant->hostInstance()->exists()) {
                $this->error('El cliente ya tiene una instancia.');

                return self::FAILURE;
            }
            $domain = strtolower((string) $this->option('domain'));
            if (! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                $this->error('Indica un dominio válido mediante --domain.');

                return self::INVALID;
            }
            $password = $this->password();
            if (config('xpanel.native_hosting.apply_system_changes') && strlen((string) $password) < 16) {
                $this->error('Usa --password-stdin con una clave inicial de al menos 16 caracteres.');

                return self::INVALID;
            }
            $instance = $provisioner->create($tenant, $domain, $password);
            $this->info($instance->uuid.' '.$instance->status);

            return self::SUCCESS;
        }

        $instance = HostInstance::query()->where('uuid', $this->argument('instance'))->firstOrFail();
        if (! in_array($action, ['apply', 'suspend', 'resume'], true)) {
            $this->error('Acción no soportada: '.$action);

            return self::INVALID;
        }

        match ($action) {
            'apply' => $provisioner->apply($instance, $this->password()),
            'suspend' => $provisioner->setSuspended($instance, true),
            'resume' => $provisioner->setSuspended($instance, false),
        };

        $this->info($instance->fresh()->uuid.' '.$instance->fresh()->status);

        return self::SUCCESS;
    }

    private function password(): ?string
    {
        if (! $this->option('password-stdin')) {
            return null;
        }

        return rtrim((string) stream_get_contents(STDIN), "\r\n");
    }
}
