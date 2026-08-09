<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // An "exam" is the reusable entrance-exam paper an admin builds once and
    // then assigns to one or more applicants (via students.exam_id). It can
    // be typed questions, an uploaded PDF/Word paper, or both at once - the
    // student view just shows whichever pieces are filled in.
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('instructions')->nullable();

            // Typed questions, stored as a simple ordered JSON array of
            // strings (e.g. ["What is 5 + 7?", "Name the capital of Ghana."]).
            // Kept intentionally simple (free-text answers, manually graded)
            // to match how assignments/report cards already work in this app.
            $table->json('questions')->nullable();

            // Optional uploaded question paper (PDF/Word) as an alternative
            // or supplement to typed questions.
            $table->string('file_path')->nullable();
            $table->string('file_original_name')->nullable();

            $table->foreignId('created_by_admin_id')->constrained('admins')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
