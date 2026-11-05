<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('ALTER TABLE tenants MODIFY domain VARCHAR(255) NULL'),
            'pgsql' => DB::statement('ALTER TABLE tenants ALTER COLUMN domain DROP NOT NULL'),
            default => null,
        };
    }

    public function down(): void
    {
        DB::table('tenants')->whereNull('domain')->orderBy('id')->each(function (object $tenant): void {
            DB::table('tenants')->where('id', $tenant->id)->update(['domain' => 'client-'.$tenant->id.'.invalid']);
        });
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('ALTER TABLE tenants MODIFY domain VARCHAR(255) NOT NULL'),
            'pgsql' => DB::statement('ALTER TABLE tenants ALTER COLUMN domain SET NOT NULL'),
            default => null,
        };
    }
};
