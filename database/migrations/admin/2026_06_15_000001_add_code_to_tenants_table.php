<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants') || Schema::hasColumn('tenants', 'code')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('code', 16)->nullable()->unique()->after('domain');
        });

        DB::table('tenants')
            ->whereNull('code')
            ->orderBy('id')
            ->select('id')
            ->chunkById(100, function ($tenants): void {
                foreach ($tenants as $tenant) {
                    do {
                        $code = 'X'.random_int(100000, 999999);
                    } while (DB::table('tenants')->where('code', $code)->exists());

                    DB::table('tenants')
                        ->where('id', $tenant->id)
                        ->update(['code' => $code]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasColumn('tenants', 'code')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
