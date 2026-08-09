<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The curated half of the grounding stack (chosen alongside RAG in
    // the earlier design discussion): approved, human-written teaching
    // material that the AI can use as a few-shot style/format reference -
    // NOT a source of facts (that's curriculum_document_chunks' job), but
    // a source of "this is what good DOK-2 material for this subject looks
    // like". Platform-level like documents/subjects: one bank shared
    // across every school on a curriculum, curated centrally so quality
    // stays consistent rather than drifting per-school.
    public function up(): void
    {
        Schema::create('curriculum_exemplars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('dok_level_id')->nullable()->constrained('dok_levels')->nullOnDelete();

            $table->string('class_tag', 30)->nullable(); // same loose convention as chunks - e.g. "B7/JHS1"
            $table->string('title', 200);

            // 'lesson_note', 'worksheet', 'quiz', 'exam' - kept as a plain
            // string rather than an enum: this is exactly the kind of list
            // that will grow as the assistant is used for more material
            // types, and an enum migration for every addition is friction
            // an admin-curated freetext field doesn't need.
            $table->string('material_type', 40);

            $table->longText('content');
            $table->foreignId('approved_by_platform_admin_id')->nullable()->constrained('platform_admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['curriculum_id', 'subject_id', 'material_type'], 'ce_cur_sub_mat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_exemplars');
    }
};
