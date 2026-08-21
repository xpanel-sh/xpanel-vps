<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_orders', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('status');
            $table->timestamp('paid_at')->nullable()->after('payment_reference');
            $table->foreignId('marked_paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('host_instances', function (Blueprint $table) {
            $table->text('initial_password')->nullable()->after('broker_secret');
        });

        DB::table('plan_orders')->where('status', 'pending')->update(['status' => 'active']);
        DB::table('plan_orders')->whereNotNull('activated_at')->update([
            'payment_status' => 'paid',
            'paid_at' => DB::raw('activated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('host_instances', function (Blueprint $table) {
            $table->dropColumn('initial_password');
        });

        Schema::table('plan_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marked_paid_by');
            $table->dropColumn(['payment_status', 'paid_at']);
        });
    }
};
