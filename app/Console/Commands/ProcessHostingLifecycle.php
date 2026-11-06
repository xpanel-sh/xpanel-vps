<?php

namespace App\Console\Commands;

use App\Models\HostingAccount;
use App\Models\PlanOrder;
use App\Models\SystemSetting;
use App\Services\HostInstanceProvisioner;
use App\Services\AccountNotifier;
use Illuminate\Console\Command;

class ProcessHostingLifecycle extends Command
{
    protected $signature = 'xpanel:hosting-lifecycle';
    protected $description = 'Suspend cancelled hosting accounts when their paid period ends';

    public function handle(HostInstanceProvisioner $provisioner, AccountNotifier $notifier): int
    {
        HostingAccount::query()->where('status', 'cancellation_pending')->whereNotNull('suspend_at')->where('suspend_at', '<=', now())
            ->with(['hostInstance', 'order', 'tenant'])->each(function (HostingAccount $account) use ($provisioner, $notifier): void {
                try {
                    if ($account->hostInstance && $account->hostInstance->status !== 'suspended') {
                        $provisioner->setSuspended($account->hostInstance, true);
                    }
                    $account->order?->update(['status' => PlanOrder::STATUS_CANCELLED]);
                    $account->update(['status' => 'suspended', 'suspended_at' => now()]);
                    $notifier->send(
                        $account->tenant, 'hosting.suspended', 'Hosting suspendido por cancelación',
                        "{$account->name} fue suspendido. Sus datos se conservarán hasta {$account->purge_after?->format('d/m/Y H:i')}.",
                        $account, route('client.host.account', $account, false),
                    );
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
            ->with(['hostInstance', 'order', 'tenant'])->each(function (HostingAccount $account) use ($provisioner, $retentionDays, $notifier): void {
                try {
                    if ($account->hostInstance && $account->hostInstance->status !== 'suspended') {
                        $provisioner->setSuspended($account->hostInstance, true);
                    }
                    $account->update(['status' => 'suspended_overdue', 'suspended_at' => now(), 'purge_after' => now()->addDays($retentionDays)]);
                    $notifier->send(
                        $account->tenant, 'payment.suspended', 'Hosting suspendido por pago vencido',
                        "{$account->name} fue suspendido por falta de pago. Confirma la boleta antes de terminar la retención.",
                        $account, route('client.orders.show', $account->order, false),
                    );
                    $this->info("Suspendido por impago: {$account->uuid}");
                } catch (\Throwable $exception) {
                    report($exception);
                    $this->error("No se pudo suspender {$account->uuid}: {$exception->getMessage()}");
                }
            });

        HostingAccount::query()
            ->whereIn('status', ['suspended', 'suspended_overdue'])
            ->whereNotNull('purge_after')
            ->where('purge_after', '<=', now())
            ->with('tenant')
            ->each(function (HostingAccount $account) use ($notifier): void {
                $account->update(['status' => 'purge_pending']);
                $notifier->send(
                    $account->tenant, 'hosting.retention_expired', 'Retención del hosting finalizada',
                    "Terminó la retención de {$account->name}. La cuenta continúa suspendida y requiere revisión administrativa antes de eliminar o recuperar datos.",
                    $account, route('client.host.account', $account, false),
                );
                $this->warn("Retención terminada: {$account->uuid}");
            });

        return self::SUCCESS;
    }
}
