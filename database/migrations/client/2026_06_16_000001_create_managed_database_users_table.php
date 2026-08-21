<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_database_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('managed_database_id')->constrained('managed_databases')->onDelete('cascade');
            $table->string('username', 64)->unique();
            $table->text('password');
            $table->json('privileges')->nullable();
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamps();
        });

        // Migrate existing single-user rows from managed_databases
        DB::table('managed_databases')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->get()
            ->each(function ($db) {
                DB::table('managed_database_users')->insertOrIgnore([
                    'managed_database_id' => $db->id,
                    'username' => $db->username,
                    'password' => $db->password ?? '',
                    'privileges' => json_encode(['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'INDEX', 'ALTER', 'REFERENCES']),
                    'status' => 'active',
                    'created_at' => $db->created_at,
                    'updated_at' => $db->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_database_users');
    }
};
