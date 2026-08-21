<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_broker_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('host_instance_id')->constrained()->cascadeOnDelete();
            $table->string('request_id', 64)->unique();
            $table->string('action', 64);
            $table->json('arguments');
            $table->string('status', 24)->default('received');
            $table->text('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_broker_operations');
    }
};
