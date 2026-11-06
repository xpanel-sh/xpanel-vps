<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_orders', function (Blueprint $table): void {
            $table->timestamp('payment_submitted_at')->nullable()->after('payment_reference');
            $table->text('payment_note')->nullable()->after('payment_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('plan_orders', fn (Blueprint $table) => $table->dropColumn(['payment_submitted_at', 'payment_note']));
    }
};
