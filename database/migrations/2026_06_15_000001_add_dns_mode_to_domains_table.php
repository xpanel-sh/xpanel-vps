<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->enum('dns_mode', ['xpanel_ns', 'a_record', 'cloudflare'])
                ->default('a_record')
                ->after('dns_status');
            $table->json('current_ns')->nullable()->after('dns_mode');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['dns_mode', 'current_ns']);
        });
    }
};
