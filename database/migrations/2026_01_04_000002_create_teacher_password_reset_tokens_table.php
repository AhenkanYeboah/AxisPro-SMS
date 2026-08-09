<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Backs the "teachers" password broker - kept separate from the admin/student
// tables so the same email address can't collide across roles.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_password_reset_tokens');
    }
};
