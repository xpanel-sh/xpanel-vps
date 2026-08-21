<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('billing_period_months')->default(1)->after('monthly_price');
            $table->unsignedSmallInteger('payment_due_days')->default(30)->after('billing_period_months');
        });

        Schema::create('plan_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hosting_plan_id')->constrained('hosting_plans')->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->unsignedSmallInteger('billing_period_months')->default(1);
            $table->timestamp('payment_due_at');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('service_ends_at')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_orders');

        Schema::table('hosting_plans', function (Blueprint $table) {
            $table->dropColumn(['billing_period_months', 'payment_due_days']);
        });
    }
};
