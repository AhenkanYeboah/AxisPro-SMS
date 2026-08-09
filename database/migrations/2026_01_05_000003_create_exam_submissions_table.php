<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            // Answers to the typed questions, keyed by question index
            // (e.g. {"0": "12", "1": "Accra"}) - mirrors exams.questions.
            $table->json('answers')->nullable();

            // Free-text box the applicant can use regardless of whether the
            // exam had typed questions (e.g. to answer an uploaded paper).
            $table->text('answer_text')->nullable();

            // Optional file the applicant uploads back (e.g. a photo/scan of
            // handwritten working, if the exam was an uploaded paper).
            $table->string('answer_file')->nullable();

            $table->timestamp('submitted_at')->useCurrent();

            // Filled in later by the admin after reviewing the answers.
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();

            // A given applicant can only submit once per exam.
            $table->unique(['exam_id', 'student_id'], 'unique_exam_submission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_submissions');
    }
};
