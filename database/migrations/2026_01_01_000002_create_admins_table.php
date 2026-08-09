<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('admin_id', 20)->unique();
            $table->string('username', 50)->unique();
            $table->string('email', 100)->unique();
            $table->string('password'); // hashed with bcrypt, same as Laravel default
            $table->string('full_name', 100)->nullable();
            $table->string('role', 50)->default('admin');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
