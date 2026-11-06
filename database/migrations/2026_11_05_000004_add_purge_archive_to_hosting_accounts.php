<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table): void {
            $table->string('purge_archive_status', 24)->nullable()->after('purge_after');
            $table->text('purge_archive_path')->nullable()->after('purge_archive_status');
            $table->string('purge_archive_sha256', 64)->nullable()->after('purge_archive_path');
            $table->text('purge_archive_error')->nullable()->after('purge_archive_sha256');
            $table->timestamp('purge_archive_requested_at')->nullable()->after('purge_archive_error');
            $table->timestamp('purge_archived_at')->nullable()->after('purge_archive_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table): void {
            $table->dropColumn([
                'purge_archive_status', 'purge_archive_path', 'purge_archive_sha256',
                'purge_archive_error', 'purge_archive_requested_at', 'purge_archived_at',
            ]);
        });
    }
};
