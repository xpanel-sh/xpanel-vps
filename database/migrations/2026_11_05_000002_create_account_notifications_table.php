<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hosting_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 80)->index();
            $table->string('title');
            $table->text('message');
            $table->string('action_url', 500)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->text('email_error')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_notifications');
    }
};
