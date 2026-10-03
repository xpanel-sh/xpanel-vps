<?php

namespace App\Console\Commands;

use App\Models\HostInstance;
use App\Models\HostingAccount;
use App\Models\Tenant;
use App\Services\HostInstanceProvisioner;
use Illuminate\Console\Command;

class ManageHostInstances extends Command
{
    protected $signature = 'xpanel:instances
        {action=list : list, create, apply, suspend or resume}
        {instance? : Instance UUID, or tenant ID for create}
        {--domain= : Optional custom technical domain when creating}
        {--plan= : Hosting plan ID for the new account}
        {--name= : Name of the new hosting account}
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
            $domain = strtolower((string) $this->option('domain'));
            if ($domain !== '' && ! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                $this->error('El valor de --domain no es válido.');

                return self::INVALID;
            }
            $planId = $this->option('plan') ?: $tenant->plan_id;
            if ($planId && ! \App\Models\HostingPlan::query()->whereKey($planId)->exists()) {
                $this->error('El plan indicado no existe.');

                return self::INVALID;
            }
            $account = HostingAccount::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'tenant_id' => $tenant->id,
                'hosting_plan_id' => $planId,
                'name' => $this->option('name') ?: 'Hosting '.($tenant->hostingAccounts()->count() + 1),
                'status' => 'active',
            ]);
            $password = $this->password();
            if (config('xpanel.native_hosting.apply_system_changes') && strlen((string) $password) < 16) {
                $this->error('Usa --password-stdin con una clave inicial de al menos 16 caracteres.');

                return self::INVALID;
            }
            $instance = $provisioner->create($account, $domain ?: null, $password);
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
