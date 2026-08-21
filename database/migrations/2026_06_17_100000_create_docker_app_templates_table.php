<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docker_app_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();          // URL o clase de icono
            $table->text('compose_template');            // YAML con {{VARIABLES}}
            $table->json('parameters')->nullable();      // [{key, label, type, default, required}]
            $table->boolean('is_public')->default(true); // visible para clientes
            $table->string('category')->default('other'); // database, dev-tools, cms, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docker_app_templates');
    }
};
