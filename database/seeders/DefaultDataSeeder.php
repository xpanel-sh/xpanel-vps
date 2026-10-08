<?php

namespace Database\Seeders;

use App\Models\HostingPlan;
use App\Models\NameserverSetting;
use App\Models\PlanOrder;
use App\Models\ServerNode;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        // New plans are drafts until the operator chooses actual selling prices.
        // Never overwrite an existing plan: active customers retain their terms.
        foreach ([
            ['esencial', 'Esencial', 1, 1, 2048, 50000, 25, 1, 512, 50, 128],
            ['plus', 'Plus', 3, 3, 5120, 100000, 75, 3, 768, 75, 192],
            ['pro', 'Pro', 5, 5, 10240, 200000, 150, 5, 1024, 100, 256],
            ['max', 'Max', 10, 10, 20480, 400000, 300, 10, 1536, 150, 384],
        ] as [$slug, $name, $sites, $databases, $storage, $inodes, $bandwidth, $mailboxes, $memory, $cpu, $tasks]) {
            HostingPlan::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'max_sites' => $sites,
                'max_databases' => $databases,
                'storage_mb' => $storage,
                'inode_limit' => $inodes,
                'bandwidth_gb' => $bandwidth,
                'email_accounts' => $mailboxes,
                'memory_mb' => $memory,
                'swap_mb' => 0,
                'cpu_percent' => $cpu,
                'tasks_max' => $tasks,
                'monthly_price' => 0,
                'billing_period_months' => 1,
                'payment_due_days' => 7,
                'is_active' => false,
                'description' => 'Plan en preparación; define el precio y activa solo tras comprobar los límites del servidor.',
            ]);
        }

        // Legacy plans remain available to assigned accounts but are not sold
        // to new customers. Do not alter their resource limits or price.
        HostingPlan::whereIn('slug', ['starter', 'growth'])->update(['is_active' => false]);

        SystemSetting::firstOrCreate(
            ['key' => 'app_name'],
            ['value' => 'XPanel']
        );

        NameserverSetting::firstOrCreate(
            ['name' => 'default'],
            [
                'provider' => 'xpanel',
                'is_active' => false,
            ]
        );

        ServerNode::where('auth_token', implode('_', ['secret', 'token']))->delete();

        // Native installations read the local Linux runtime directly. Older
        // releases created this row even though no daemon listened on 7070.
        ServerNode::query()
            ->where('name', 'Local Node')
            ->where('ip_address', '127.0.0.1')
            ->where('port', 7070)
            ->delete();

        if (filter_var(env('XPANEL_SEED_DEMO_USERS', false), FILTER_VALIDATE_BOOL)) {
            $adminPassword = (string) env('XPANEL_DEMO_ADMIN_PASSWORD', '');
            $clientPassword = (string) env('XPANEL_DEMO_CLIENT_PASSWORD', '');
            if ($adminPassword !== '' && $clientPassword !== '') {
                User::updateOrCreate(
                    ['email' => 'admin@xpanel.local'],
                    ['name' => 'XPanel Admin', 'password' => Hash::make($adminPassword), 'role' => 'admin']
                );
                $client = User::updateOrCreate(
                    ['email' => 'client@xpanel.local'],
                    ['name' => 'Cliente Demo', 'password' => Hash::make($clientPassword), 'role' => 'client']
                );
                $demoTenant = Tenant::updateOrCreate(
                    ['domain' => 'cliente.xpanel.local'],
                    [
                        'name' => 'Cliente Demo',
                        'code' => 'XDEMO001',
                        'user_id' => $client->id,
                        'plan_id' => HostingPlan::where('slug', 'esencial')->value('id'),
                        'status' => 'active',
                    ]
                );
                $demoPlan = HostingPlan::where('slug', 'plus')->first();
                if ($demoPlan) {
                    $demoTenant->update(['plan_id' => $demoPlan->id, 'status' => 'active']);
                    PlanOrder::firstOrCreate(
                        ['number' => 'XP-DEMO-0001'],
                        [
                            'tenant_id' => $demoTenant->id,
                            'hosting_plan_id' => $demoPlan->id,
                            'status' => PlanOrder::STATUS_ACTIVE,
                            'payment_status' => PlanOrder::PAYMENT_PENDING,
                            'amount' => (float) $demoPlan->monthly_price * $demoPlan->billing_period_months,
                            'currency' => 'USD',
                            'billing_period_months' => $demoPlan->billing_period_months,
                            'payment_due_at' => now()->addDays($demoPlan->payment_due_days),
                            'activated_at' => now(),
                            'service_ends_at' => now()->addMonths($demoPlan->billing_period_months),
                        ]
                    );
                }
            }
        }

    }
}
