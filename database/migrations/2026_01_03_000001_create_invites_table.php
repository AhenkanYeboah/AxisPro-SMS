<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invites', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->enum('type', ['admin', 'teacher']);
            // Optional: lock an invite to one email address. Null = anyone with
            // the code can redeem it (still single-use).
            $table->string('email', 150)->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('used_by_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invites');
    }
};
