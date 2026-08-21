<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_instances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('panel_domain')->unique();
            $table->string('system_user', 32)->unique();
            $table->string('release_path');
            $table->string('instance_root');
            $table->string('database_path');
            $table->string('php_version', 8)->default('8.3');
            $table->string('version')->nullable();
            $table->string('update_channel', 24)->default('stable');
            $table->string('status', 24)->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_instances');
    }
};
