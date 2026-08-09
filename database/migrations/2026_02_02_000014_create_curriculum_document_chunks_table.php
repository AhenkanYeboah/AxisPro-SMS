<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The actual retrievable unit. One row per indicator-sized chunk of a
    // syllabus document (typically one content standard + its indicators
    // and exemplars, per the NaCCA STRAND/SUB-STRAND/CONTENT STANDARD/
    // INDICATOR structure we saw when reading the real document) - small
    // enough to be a focused, accurate citation; big enough to keep an
    // indicator's exemplars attached to it, since those are what make the
    // generated material concrete rather than generic.
    //
    // Retrieval here is FULLTEXT keyword search (MATCH AGAINST), not
    // vector similarity - deliberately, for this phase: no vector-capable
    // datastore is set up (this runs on plain MySQL via XAMPP), and a
    // syllabus's own strand/sub-strand/indicator vocabulary is exactly the
    // kind of specific terminology keyword search is good at matching.
    // Swapping in embeddings later is additive (a new column + a new
    // retrieval path), not a rebuild of this table.
    public function up(): void
    {
        Schema::create('curriculum_document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_document_id')->constrained('curriculum_documents')->cascadeOnDelete();

            // Denormalised from the parent document onto every chunk
            // on purpose - every retrieval query filters by these, and
            // querying the chunks table directly (its own indexes) beats
            // joining back to curriculum_documents on every lookup.
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();

            // Free text class marker as it appears in the source document,
            // e.g. "B7/JHS1" or "Year 7" - extracted heuristically during
            // ingestion from strand headers. Matched loosely (LIKE) against
            // a school's class_levels.name at retrieval time rather than a
            // hard FK, because syllabus documents don't share exact naming
            // with however an individual school labels its classes.
            $table->string('class_tag', 30)->nullable();

            $table->string('strand', 150)->nullable();
            $table->string('sub_strand', 150)->nullable();
            $table->string('indicator_code', 40)->nullable(); // e.g. "B7/JHS1.1.1.1.1"

            $table->unsignedInteger('chunk_index'); // position within the document, for ordering/context
            $table->unsignedInteger('page_number')->nullable();
            $table->longText('content');

            $table->timestamps();

            $table->index(['curriculum_id', 'subject_id', 'class_tag'],'cdc_cur_sub_class_idx');
            $table->fullText('content');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_document_chunks');
    }
};
