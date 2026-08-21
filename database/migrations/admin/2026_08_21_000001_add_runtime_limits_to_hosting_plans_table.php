<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_plans', function (Blueprint $table): void {
            $table->unsignedInteger('memory_mb')->default(512)->after('email_accounts');
            $table->unsignedInteger('swap_mb')->default(0)->after('memory_mb');
            $table->unsignedSmallInteger('cpu_percent')->default(100)->after('swap_mb');
            $table->unsignedInteger('tasks_max')->default(256)->after('cpu_percent');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_plans', function (Blueprint $table): void {
            $table->dropColumn(['memory_mb', 'swap_mb', 'cpu_percent', 'tasks_max']);
        });
    }
};
