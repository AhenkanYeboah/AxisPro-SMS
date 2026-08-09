<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('teacher_id', 20)->unique();
            $table->string('username', 50)->unique();
            $table->string('email', 100)->unique();
            $table->string('password')->nullable(); // null until teacher sets it
            $table->string('full_name', 100);
            $table->string('assigned_class', 20)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
