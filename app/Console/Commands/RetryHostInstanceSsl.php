<?php

namespace App\Console\Commands;

use App\Models\HostInstance;
use App\Services\HostInstanceCertificateProvisioner;
use Illuminate\Console\Command;

class RetryHostInstanceSsl extends Command
{
    protected $signature = 'xpanel:instances:retry-ssl {--instance=}';
    protected $description = 'Reintenta certificados SSL de instancias Host activas';

    public function handle(HostInstanceCertificateProvisioner $certificates): int
    {
        HostInstance::query()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->where('ssl_status', '!=', 'active')
                    ->orWhereHas('hostingAccount', fn ($account) => $account
                        ->whereNotNull('custom_panel_domain')
                        ->where('custom_domain_status', '!=', 'active'));
            })
            ->when($this->option('instance'), fn ($query, $uuid) => $query->where('uuid', $uuid))
            ->where(fn ($query) => $query->whereNull('ssl_attempted_at')->orWhere('ssl_attempted_at', '<=', now()->subMinutes(10)))
            ->with(['tenant.user', 'hostingAccount'])
            ->each(fn (HostInstance $instance) => $certificates->issue($instance));

        return self::SUCCESS;
    }
}
