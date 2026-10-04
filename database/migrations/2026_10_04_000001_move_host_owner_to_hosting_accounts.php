<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->boolean('access_ready')->default(true)->after('user_id');
        });

        Schema::table('hosting_accounts', function (Blueprint $table): void {
            $table->string('admin_name')->nullable()->after('name');
            $table->string('admin_email')->nullable()->after('admin_name');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('access_ready');
        });

        Schema::table('hosting_accounts', function (Blueprint $table): void {
            $table->dropColumn(['admin_name', 'admin_email']);
        });
    }
};
