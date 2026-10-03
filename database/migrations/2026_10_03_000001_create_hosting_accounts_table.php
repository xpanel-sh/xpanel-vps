<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_accounts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hosting_plan_id')->nullable()->constrained('hosting_plans')->nullOnDelete();
            $table->foreignId('plan_order_id')->nullable()->unique()->constrained('plan_orders')->nullOnDelete();
            $table->string('name');
            $table->string('status', 24)->default('active');
            $table->string('custom_panel_domain')->nullable()->unique();
            $table->string('custom_domain_status', 24)->default('not_configured');
            $table->text('custom_domain_last_error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::table('host_instances', function (Blueprint $table): void {
            $table->foreignId('hosting_account_id')->nullable()->unique()->after('tenant_id')
                ->constrained('hosting_accounts')->cascadeOnDelete();
        });

        Schema::table('plan_orders', function (Blueprint $table): void {
            $table->foreignId('hosting_account_id')->nullable()->after('tenant_id')
                ->constrained('hosting_accounts')->nullOnDelete();
        });

        DB::table('host_instances')->orderBy('id')->each(function (object $instance): void {
            $tenant = DB::table('tenants')->where('id', $instance->tenant_id)->first();
            if (! $tenant) {
                return;
            }

            $order = DB::table('plan_orders')
                ->where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->latest('id')
                ->first();
            $accountId = DB::table('hosting_accounts')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'hosting_plan_id' => $order?->hosting_plan_id ?? $tenant->plan_id,
                'plan_order_id' => $order?->id,
                'name' => 'Hosting principal',
                'status' => $instance->status === 'suspended' ? 'suspended' : 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('host_instances')->where('id', $instance->id)->update(['hosting_account_id' => $accountId]);
            if ($order) {
                DB::table('plan_orders')->where('id', $order->id)->update(['hosting_account_id' => $accountId]);
            }
        });

        Schema::table('host_instances', function (Blueprint $table): void {
            $table->dropUnique('host_instances_tenant_id_unique');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('host_instances', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->unique('tenant_id');
            $table->dropConstrainedForeignId('hosting_account_id');
        });
        Schema::table('plan_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('hosting_account_id');
        });
        Schema::dropIfExists('hosting_accounts');
    }
};
