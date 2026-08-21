<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docker_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('docker_app_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                     // nombre visible
            $table->string('slug');                     // identificador único por tenant
            $table->text('compose_yaml');               // YAML final (variables ya interpoladas)
            $table->json('env_values')->nullable();     // parámetros usados (para mostrar en form)
            $table->string('domain')->nullable();       // subdominio si tiene URL pública
            $table->string('status')->default('stopped'); // running | stopped | error | provisioning
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docker_instances');
    }
};
