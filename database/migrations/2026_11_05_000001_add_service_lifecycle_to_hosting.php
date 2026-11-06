<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table): void {
            $table->timestamp('cancellation_requested_at')->nullable()->after('status');
            $table->timestamp('suspend_at')->nullable()->after('cancellation_requested_at');
            $table->timestamp('suspended_at')->nullable()->after('suspend_at');
            $table->timestamp('purge_after')->nullable()->after('suspended_at');
        });
        Schema::table('plan_orders', function (Blueprint $table): void {
            $table->string('kind')->default('contract')->after('number');
            $table->foreignId('previous_hosting_plan_id')->nullable()->after('hosting_plan_id')->constrained('hosting_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plan_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('previous_hosting_plan_id');
            $table->dropColumn('kind');
        });
        Schema::table('hosting_accounts', function (Blueprint $table): void {
            $table->dropColumn(['cancellation_requested_at', 'suspend_at', 'suspended_at', 'purge_after']);
        });
    }
};
