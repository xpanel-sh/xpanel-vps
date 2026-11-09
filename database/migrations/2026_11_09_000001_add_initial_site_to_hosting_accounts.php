<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table): void {
            $table->string('initial_site_domain')->nullable()->unique();
            $table->string('initial_site_status', 24)->nullable();
            $table->text('initial_site_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table): void {
            $table->dropUnique(['initial_site_domain']);
            $table->dropColumn(['initial_site_domain', 'initial_site_status', 'initial_site_error']);
        });
    }
};
