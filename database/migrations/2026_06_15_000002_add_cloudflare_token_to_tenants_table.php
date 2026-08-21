<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tenants', 'cloudflare_api_token')) {
            return;
        }
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('cloudflare_api_token', 512)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('cloudflare_api_token');
        });
    }
};
