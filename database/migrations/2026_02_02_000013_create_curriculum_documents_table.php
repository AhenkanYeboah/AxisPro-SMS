<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A source document (NaCCA syllabus PDF, Cambridge scheme of work,
    // etc.) uploaded once and shared by every school on that curriculum -
    // platform-level, like curricula/subjects themselves. Scoped to
    // curriculum + subject; NOT to a single class_level, because real
    // syllabus PDFs span multiple classes in one file (e.g. the NaCCA
    // Science document covers B7, B8 and B9 together) - which class a
    // given piece of content belongs to is captured per-chunk instead
    // (curriculum_document_chunks.class_tag), not at the document level.
    public function up(): void
    {
        Schema::create('curriculum_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();

            $table->string('title', 200);
            $table->string('file_path'); // storage path to the source PDF
            $table->string('source_url')->nullable(); // e.g. the NaCCA URL it was downloaded from, for provenance

            // 'syllabus' = official curriculum document (content standards,
            // indicators, exemplars). 'dok_reference' = a DOK/cognitive-
            // rigour guide (like the Hess crosswalk) used as a reasoning
            // aid rather than subject content - kept in the same table
            // since both get ingested/chunked/retrieved the same way, just
            // distinguished by type at retrieval time.
            $table->enum('document_type', ['syllabus', 'dok_reference', 'other'])->default('syllabus');

            $table->enum('ingestion_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('ingestion_error')->nullable();

            $table->foreignId('uploaded_by_platform_admin_id')->nullable()->constrained('platform_admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_documents');
    }
};
