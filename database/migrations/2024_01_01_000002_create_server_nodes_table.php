<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Servidor USA 1"
            $table->string('ip_address');
            $table->integer('port')->default(7070); // Puerto del Daemon
            $table->string('auth_token'); // Token para hablar con el Daemon remota
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_nodes');
    }
};
