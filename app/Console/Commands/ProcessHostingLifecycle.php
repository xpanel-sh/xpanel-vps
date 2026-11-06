<?php

namespace App\Console\Commands;

use App\Models\HostingAccount;
use App\Models\PlanOrder;
use App\Models\SystemSetting;
use App\Services\HostInstanceProvisioner;
use Illuminate\Console\Command;

class ProcessHostingLifecycle extends Command
{
    protected $signature = 'xpanel:hosting-lifecycle';
    protected $description = 'Suspend cancelled hosting accounts when their paid period ends';

    public function handle(HostInstanceProvisioner $provisioner): int
    {
        HostingAccount::query()->where('status', 'cancellation_pending')->whereNotNull('suspend_at')->where('suspend_at', '<=', now())
            ->with(['hostInstance', 'order'])->each(function (HostingAccount $account) use ($provisioner): void {
                try {
                    if ($account->hostInstance && $account->hostInstance->status !== 'suspended') {
                        $provisioner->setSuspended($account->hostInstance, true);
                    }
                    $account->order?->update(['status' => PlanOrder::STATUS_CANCELLED]);
                    $account->update(['status' => 'suspended', 'suspended_at' => now()]);
                    $this->info("Suspendido: {$account->uuid}");
                } catch (\Throwable $exception) {
                    report($exception);
                    $this->error("No se pudo suspender {$account->uuid}: {$exception->getMessage()}");
                }
            });

        $graceDays = (int) SystemSetting::get('service_grace_days', '7');
        $retentionDays = (int) SystemSetting::get('cancelled_data_retention_days', '30');
        HostingAccount::query()->where('status', 'active')->whereHas('order', fn ($query) => $query
            ->where('status', PlanOrder::STATUS_ACTIVE)
            ->where('payment_status', PlanOrder::PAYMENT_PENDING)
            ->where('payment_due_at', '<=', now()->subDays($graceDays)))
            ->with(['hostInstance', 'order'])->each(function (HostingAccount $account) use ($provisioner, $retentionDays): void {
                try {
                    if ($account->hostInstance && $account->hostInstance->status !== 'suspended') {
                        $provisioner->setSuspended($account->hostInstance, true);
                    }
                    $account->update(['status' => 'suspended_overdue', 'suspended_at' => now(), 'purge_after' => now()->addDays($retentionDays)]);
                    $this->info("Suspendido por impago: {$account->uuid}");
                } catch (\Throwable $exception) {
                    report($exception);
                    $this->error("No se pudo suspender {$account->uuid}: {$exception->getMessage()}");
                }
            });

        return self::SUCCESS;
    }
}
