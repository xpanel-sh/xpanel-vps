<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('host_instances', function (Blueprint $table): void {
            $table->string('update_status', 24)->nullable();
            $table->text('update_error')->nullable();
            $table->timestamp('update_started_at')->nullable();
            $table->timestamp('update_finished_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('host_instances', function (Blueprint $table): void {
            $table->dropColumn(['update_status', 'update_error', 'update_started_at', 'update_finished_at']);
        });
    }
};
