<?php

namespace Tests\Feature;

use App\Models\HostingAccount;
use App\Models\HostingPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\HostInstanceProvisioner;
use App\Services\HostInstanceResourceLimiter;
use App\Services\ServerCommandRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class HostDiskQuotaProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_reconciles_existing_accounts_and_cataloged_databases(): void
    {
        $update = file_get_contents(base_path('scripts/xpanel-update.sh'));
        $quota = file_get_contents(base_path('scripts/xpanel-project-quota.sh'));
        $helper = file_get_contents(base_path('scripts/xpanel-instance-helper.sh'));

        $this->assertStringContainsString('xpanel:system-reconcile --repair', $update);
        $this->assertStringContainsString('reconcile-databases "$uuid" "$user" "$project_id"', $quota);
        $this->assertStringContainsString('FROM site_databases WHERE status', $quota);
        $this->assertStringContainsString('tag-database "$uuid" "$user" "$project_id" "$database"', $quota);
        $this->assertStringContainsString('if [[ "${1:-}" == "verify-instance" ]]', $helper);
        $this->assertStringContainsString('system_revision', $helper);
    }

    private function account(int $inodes = 50000): HostingAccount
    {
        $plan = HostingPlan::create([
            'name' => 'Test', 'slug' => 'test', 'max_sites' => 1, 'max_databases' => 1,
            'storage_mb' => 2048, 'inode_limit' => $inodes, 'monthly_price' => 5,
        ]);
        $tenant = Tenant::create([
            'name' => 'Cliente', 'domain' => 'client.test',
            'user_id' => User::factory()->create(['role' => 'client'])->id,
            'status' => 'active',
        ]);

        return HostingAccount::create([
            'uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id,
            'hosting_plan_id' => $plan->id, 'name' => 'Hosting', 'status' => 'active',
        ]);
    }

    public function test_quota_preflight_fails_before_creating_a_host_instance(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', true);
        $account = $this->account();
        $this->mock(ServerCommandRunner::class)
            ->shouldReceive('run')->once()->withArgs(fn (array $args) =>
                $args[3] === 'quota-status'
            )->andThrow(new RuntimeException('ext4 is not mounted with prjquota'));

        $this->expectException(RuntimeException::class);
        try {
            app(HostInstanceProvisioner::class)->create($account);
        } finally {
            $this->assertDatabaseCount('host_instances', 0);
        }
    }

    public function test_managed_host_requires_inode_limit(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', true);
        $account = $this->account(0);

        $this->expectExceptionMessage('límites de disco e inodos');
        try {
            app(HostInstanceProvisioner::class)->create($account);
        } finally {
            $this->assertDatabaseCount('host_instances', 0);
        }
    }

    public function test_project_id_and_limits_come_from_the_host_instance_and_plan(): void
    {
        config()->set('xpanel.native_hosting.apply_system_changes', false);
        $instance = app(HostInstanceProvisioner::class)->create($this->account());

        $this->assertSame(
            [100000 + $instance->id, 2048, 50000],
            app(HostInstanceResourceLimiter::class)->diskArguments($instance),
        );
    }
}
