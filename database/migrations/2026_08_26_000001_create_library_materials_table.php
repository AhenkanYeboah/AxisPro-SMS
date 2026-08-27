<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A school's own reading library - textbooks, past questions, worksheets,
    // and research material that admins/teachers upload for students to read
    // on the student portal. Unlike curriculum_exemplars/curriculum_documents
    // (platform-level, shared across schools), this is school-owned content:
    // every row is scoped to one school via BelongsToSchool, matching
    // assignments/timetables/etc.
    //
    // subject_id and class_level_id are BOTH nullable - a material can be
    // tagged to either, both, or neither. Untagged ("general library") items
    // are visible to every admitted student in the school; a class_level_id
    // narrows it to just that class; a subject_id narrows it further within
    // that class (or, alone, flags it as subject-specific across classes -
    // e.g. a Mathematics reference useful at any level).
    public function up(): void
    {
        Schema::create('library_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('class_level_id')->nullable()->constrained('class_levels')->nullOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable();

            // Freetext like curriculum_exemplars.material_type, for the same
            // reason - this list (textbook, past_question, worksheet,
            // supplementary, research) will grow with real usage rather than
            // needing an enum migration each time.
            $table->string('category', 40)->default('supplementary');

            $table->string('file_path');
            $table->string('file_type', 10); // extension, e.g. 'pdf' - drives the reader UI
            $table->unsignedBigInteger('file_size')->default(0); // bytes, for display

            // False = view-only in the in-browser reader (e.g. a licensed
            // textbook the school can't redistribute). True by default since
            // most material (school-authored notes, past questions) has no
            // such restriction and forcing online-only reading costs data.
            $table->boolean('allow_download')->default(true);

            // Who uploaded it - admin or teacher, exactly one of the two FKs
            // below is set. Kept as two nullable FKs rather than a single
            // polymorphic column since every other uploader-attribution
            // column in this codebase (e.g. curriculum_documents.uploaded_by_platform_admin_id)
            // follows this same plain-FK convention.
            $table->enum('uploaded_by_type', ['admin', 'teacher']);
            $table->foreignId('uploaded_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('uploaded_by_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();

            $table->timestamps();

            $table->index(['school_id', 'class_level_id', 'subject_id'], 'library_school_class_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_materials');
    }
};
