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
        HostingPlan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'max_sites' => 1,
                'max_databases' => 1,
                'storage_mb' => 1024,
                'inode_limit' => 50000,
                'bandwidth_gb' => 10,
                'email_accounts' => 0,
                'memory_mb' => 512,
                'swap_mb' => 0,
                'cpu_percent' => 50,
                'tasks_max' => 128,
                'monthly_price' => 0,
                'billing_period_months' => 1,
                'payment_due_days' => 30,
                'is_active' => true,
                'description' => 'Plan base para instalaciones pequeñas y primeras pruebas.',
            ]
        );

        HostingPlan::updateOrCreate(
            ['slug' => 'growth'],
            [
                'name' => 'Growth',
                'max_sites' => 5,
                'max_databases' => 5,
                'storage_mb' => 10240,
                'inode_limit' => 250000,
                'bandwidth_gb' => 100,
                'email_accounts' => 10,
                'memory_mb' => 2048,
                'swap_mb' => 512,
                'cpu_percent' => 200,
                'tasks_max' => 512,
                'monthly_price' => 9.99,
                'billing_period_months' => 1,
                'payment_due_days' => 7,
                'is_active' => true,
                'description' => 'Plan para clientes con varios sitios y mayor capacidad.',
            ]
        );

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
                        'plan_id' => HostingPlan::where('slug', 'starter')->value('id'),
                        'status' => 'active',
                    ]
                );
                $growthPlan = HostingPlan::where('slug', 'growth')->first();
                if ($growthPlan) {
                    $demoTenant->update(['plan_id' => $growthPlan->id, 'status' => 'active']);
                    PlanOrder::firstOrCreate(
                        ['number' => 'XP-DEMO-0001'],
                        [
                            'tenant_id' => $demoTenant->id,
                            'hosting_plan_id' => $growthPlan->id,
                            'status' => PlanOrder::STATUS_ACTIVE,
                            'payment_status' => PlanOrder::PAYMENT_PENDING,
                            'amount' => (float) $growthPlan->monthly_price * $growthPlan->billing_period_months,
                            'currency' => 'USD',
                            'billing_period_months' => $growthPlan->billing_period_months,
                            'payment_due_at' => now()->addDays($growthPlan->payment_due_days),
                            'activated_at' => now(),
                            'service_ends_at' => now()->addMonths($growthPlan->billing_period_months),
                        ]
                    );
                }
            }
        }

    }
}
