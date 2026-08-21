<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('provisioning_driver', 20)->default('native');
            $table->string('system_user', 32)->nullable()->unique();
            $table->string('document_root')->nullable();
        });

        DB::table('sites')
            ->whereIn('project_type', ['node', 'python'])
            ->update(['provisioning_driver' => 'docker']);
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropUnique(['system_user']);
            $table->dropColumn(['provisioning_driver', 'system_user', 'document_root']);
        });
    }
};
