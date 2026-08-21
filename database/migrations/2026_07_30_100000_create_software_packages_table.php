<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_node_id')->constrained()->cascadeOnDelete();
            $table->string('slug'); // matches the daemon catalog slug: nginx, apache, openlitespeed, php81..php84
            $table->string('category'); // webserver | php
            $table->boolean('enabled_for_clients')->default(false); // offered in the site-create engine picker
            $table->timestamps();

            $table->unique(['server_node_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_packages');
    }
};
