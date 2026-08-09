<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-student delivery log for a notice. Deliberately no school_id/
        // BelongsToSchool here - it's always reached through its parent
        // notice (which IS school-scoped), so scoping it independently
        // would be redundant rather than protective.
        Schema::create('notice_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->enum('email_status', ['pending', 'sent', 'failed', 'skipped'])->default('pending');
            $table->enum('sms_status', ['pending', 'sent', 'failed', 'skipped'])->default('pending');
            $table->string('error_message', 255)->nullable();
            $table->timestamps();

            $table->index(['notice_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_recipients');
    }
};
