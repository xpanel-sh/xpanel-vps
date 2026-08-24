<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_plans', function (Blueprint $table): void {
            $table->unsignedInteger('inode_limit')->default(100000)->after('storage_mb');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_plans', fn (Blueprint $table) => $table->dropColumn('inode_limit'));
    }
};
