<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('host_instances', function (Blueprint $table): void {
            $table->string('update_stage', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('host_instances', function (Blueprint $table): void {
            $table->dropColumn('update_stage');
        });
    }
};
