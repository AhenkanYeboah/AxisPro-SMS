<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('term', 20);
            $table->string('file_path');
            $table->timestamp('uploaded_at')->useCurrent();

            // Same rule as the original's unique_report key: re-uploading for
            // the same student+term replaces the existing report card
            // (handled in the controller as updateOrCreate) instead of
            // creating a duplicate row.
            $table->unique(['student_id', 'term'], 'unique_report');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};
