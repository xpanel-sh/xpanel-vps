<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_broker_resources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('host_instance_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('name', 255);
            $table->timestamps();
            $table->unique(['type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_broker_resources');
    }
};
