<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('host_instances', function (Blueprint $table): void {
            $table->text('broker_secret')->nullable()->after('database_path');
        });
    }

    public function down(): void
    {
        Schema::table('host_instances', fn (Blueprint $table) => $table->dropColumn('broker_secret'));
    }
};
