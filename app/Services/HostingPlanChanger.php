<?php

namespace App\Services;

use App\Models\HostingAccount;
use App\Models\HostingPlan;
use App\Models\PlanOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class HostingPlanChanger
{
    public function __construct(
        private readonly HostInstanceProvisioner $provisioner,
        private readonly HostInstanceMetrics $metrics,
        private readonly HostInstanceDatabaseReader $database,
    ) {}

    public function change(HostingAccount $account, HostingPlan $plan): PlanOrder
    {
        $account->loadMissing(['plan', 'hostInstance.brokerResources']);
        if ($account->status !== 'active') {
            throw new RuntimeException('Solo se puede cambiar el plan de un hosting activo.');
        }
        if ($account->hosting_plan_id === $plan->id) {
            throw new RuntimeException('El hosting ya utiliza ese plan.');
        }
        if (! $plan->is_active) {
            throw new RuntimeException('El plan seleccionado no está disponible.');
        }
        $this->provisioner->assertPlanReady($plan);
        $this->assertFits($account, $plan);

        $previous = $account->plan;
        $previousOrder = $account->order;
        $order = DB::transaction(function () use ($account, $plan, $previous, $previousOrder): PlanOrder {
            $order = PlanOrder::create([
                'number' => 'XP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'kind' => 'plan_change',
                'tenant_id' => $account->tenant_id,
                'hosting_account_id' => $account->id,
                'hosting_plan_id' => $plan->id,
                'previous_hosting_plan_id' => $previous?->id,
                'status' => PlanOrder::STATUS_ACTIVE,
                'payment_status' => PlanOrder::PAYMENT_PENDING,
                'amount' => (float) $plan->monthly_price * $plan->billing_period_months,
                'currency' => 'USD',
                'billing_period_months' => $plan->billing_period_months,
                'payment_due_at' => now()->addDays($plan->payment_due_days),
                'activated_at' => now(),
                'service_ends_at' => now()->addMonths($plan->billing_period_months),
            ]);
            $previousOrder?->update(['status' => PlanOrder::STATUS_SUPERSEDED]);
            $account->update(['hosting_plan_id' => $plan->id, 'plan_order_id' => $order->id]);

            return $order;
        });

        try {
            if ($account->hostInstance) {
                $this->provisioner->apply($account->hostInstance->fresh(), null);
            }
        } catch (\Throwable $exception) {
            DB::transaction(function () use ($account, $previous, $previousOrder, $order): void {
                $account->update(['hosting_plan_id' => $previous?->id, 'plan_order_id' => $previousOrder?->id]);
                $previousOrder?->update(['status' => PlanOrder::STATUS_ACTIVE]);
                $order->delete();
            });
            throw $exception;
        }

        return $order;
    }

    private function assertFits(HostingAccount $account, HostingPlan $plan): void
    {
        $instance = $account->hostInstance;
        if (! $instance) return;
        $sites = $instance->brokerResources->where('type', 'site-domain')->reject(fn ($resource) => str_starts_with($resource->name, '*.'))->count();
        $databases = $instance->brokerResources->where('type', 'database-name')->count();
        $mailAccounts = null;
        try {
            $counts = $this->database->query($instance, 'usage-counts', 'all');
            $sites = (int) ($counts['sites'] ?? $sites);
            $databases = (int) ($counts['databases'] ?? $databases);
            $mailAccounts = (int) ($counts['mail_accounts'] ?? 0);
        } catch (\Throwable $exception) {
            if (config('xpanel.native_hosting.apply_system_changes')) {
                throw new RuntimeException('No se pudo comprobar el consumo actual antes de cambiar el plan.', 0, $exception);
            }
        }
        if ($plan->max_sites > 0 && $sites > $plan->max_sites) {
            throw new RuntimeException("El hosting utiliza {$sites} sitios y el plan permite {$plan->max_sites}.");
        }
        if ($plan->max_databases > 0 && $databases > $plan->max_databases) {
            throw new RuntimeException("El hosting utiliza {$databases} bases y el plan permite {$plan->max_databases}.");
        }
        if ($mailAccounts !== null && $plan->email_accounts > 0 && $mailAccounts > $plan->email_accounts) {
            throw new RuntimeException("El hosting utiliza {$mailAccounts} cuentas de correo y el plan permite {$plan->email_accounts}.");
        }
        $usage = $this->metrics->for($instance);
        if ($plan->storage_mb > 0 && (int) $usage['disk_bytes'] > $plan->storage_mb * 1024 * 1024) {
            throw new RuntimeException('El espacio utilizado supera el almacenamiento del plan seleccionado.');
        }
    }
}
