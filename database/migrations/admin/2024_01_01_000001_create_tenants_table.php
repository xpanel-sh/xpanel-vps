<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants')) {
            return;
        }

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre empresa
            $table->string('domain')->unique(); // dominio.com
            $table->string('status')->default('active'); // active, suspended
            $table->foreignId('plan_id')->nullable()->constrained('hosting_plans')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Dueño
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
