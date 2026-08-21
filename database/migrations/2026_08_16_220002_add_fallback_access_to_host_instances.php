<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('host_instances', function (Blueprint $table) {
            $table->unsignedSmallInteger('access_port')->nullable()->unique()->after('panel_domain');
            $table->string('ssl_status', 24)->default('pending')->after('status');
            $table->text('ssl_last_error')->nullable()->after('last_error');
            $table->timestamp('ssl_attempted_at')->nullable()->after('ssl_last_error');
        });

        $port = 10000;
        foreach (DB::table('host_instances')->orderBy('id')->pluck('id') as $id) {
            DB::table('host_instances')->where('id', $id)->update(['access_port' => $port++]);
        }
    }

    public function down(): void
    {
        Schema::table('host_instances', function (Blueprint $table) {
            $table->dropUnique(['access_port']);
            $table->dropColumn(['access_port', 'ssl_status', 'ssl_last_error', 'ssl_attempted_at']);
        });
    }
};
