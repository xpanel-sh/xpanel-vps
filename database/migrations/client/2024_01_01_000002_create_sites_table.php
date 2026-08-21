<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sites')) {
            return;
        }

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_node_id')->constrained(); // En qué servidor vive
            $table->string('domain'); // midominio.com
            $table->string('project_type')->default('php'); // php, node, static, java
            $table->string('web_server')->default('apache'); // apache, nginx, traefik
            $table->string('php_version')->default('8.2');
            $table->string('status')->default('creating');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
