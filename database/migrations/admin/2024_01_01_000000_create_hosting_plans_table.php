<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hosting_plans')) {
            return;
        }

        Schema::create('hosting_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('max_sites')->default(1);
            $table->unsignedInteger('max_databases')->default(1);
            $table->unsignedInteger('storage_mb')->default(1024);
            $table->unsignedInteger('bandwidth_gb')->default(10);
            $table->unsignedInteger('email_accounts')->default(0);
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_plans');
    }
};
