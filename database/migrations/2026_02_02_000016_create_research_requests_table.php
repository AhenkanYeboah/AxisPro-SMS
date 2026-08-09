<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Every generation a teacher runs gets logged here - not just the
    // output, but WHICH chunks grounded it (source_chunk_ids) and what DOK
    // level the AI judged the material to sit at (assigned_dok_level_id,
    // its own judgement call, distinct from dok_levels which is just the
    // reference definitions). This matters for two reasons: (1) a teacher
    // can see exactly which syllabus indicator their material came from -
    // the "not hallucinating" requirement is only meaningful if it's
    // checkable, and (2) a highly-rated request is the natural pipeline
    // into curriculum_exemplars later (platform admin reviews, promotes
    // it) rather than exemplars being written from scratch.
    public function up(): void
    {
        Schema::create('research_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('class_level_id')->constrained('class_levels')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            $table->string('topic', 200); // teacher's freetext request, e.g. "the water cycle"
            $table->string('material_type', 40); // 'lesson_note', 'worksheet', 'quiz', etc. - see curriculum_exemplars

            // What the teacher asked for, if they overrode it - otherwise
            // null, meaning "let the AI judge from the matched indicator"
            // (see the earlier finding that DOK isn't fixed per class).
            $table->foreignId('requested_dok_level_id')->nullable()->constrained('dok_levels')->nullOnDelete();

            // What the AI actually grounded the material in and judged the
            // level to be - the auditable half of "not hallucinating".
            $table->json('source_chunk_ids')->nullable();
            $table->foreignId('assigned_dok_level_id')->nullable()->constrained('dok_levels')->nullOnDelete();

            $table->longText('generated_content')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();

            // Lightweight feedback loop - a teacher marking a result useful
            // is the signal a platform admin uses to decide what's worth
            // promoting into curriculum_exemplars.
            $table->boolean('marked_helpful')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_requests');
    }
};
