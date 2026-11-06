<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table): void {
            $table->timestamp('purge_archive_verified_at')->nullable()->after('purge_archived_at');
            $table->text('purge_error')->nullable()->after('purge_archive_verified_at');
            $table->timestamp('purge_requested_at')->nullable()->after('purge_error');
            $table->timestamp('purged_at')->nullable()->after('purge_requested_at');
            $table->text('restore_error')->nullable()->after('purged_at');
            $table->timestamp('restore_requested_at')->nullable()->after('restore_error');
            $table->timestamp('restored_at')->nullable()->after('restore_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table): void {
            $table->dropColumn([
                'purge_archive_verified_at', 'purge_error', 'purge_requested_at', 'purged_at',
                'restore_error', 'restore_requested_at', 'restored_at',
            ]);
        });
    }
};
