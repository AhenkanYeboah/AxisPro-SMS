<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Two pools living in the same table, distinguished by this one
    // nullable column: NULL = platform-curated, shared by every school on
    // that curriculum (the original design - official-quality style
    // references, gatekept by the platform admin). A set school_id = that
    // school's own local exemplar, visible only to itself (its own exam
    // conventions, lesson formats, etc.), managed by its own admin.
    //
    // Deliberately NOT split into a separate table: retrieval needs both
    // pools queried together (see SyllabusRetrievalService::findExemplars),
    // and the two kinds of row differ only in ownership/visibility, not
    // shape - a second table would just be this one with a school_id NOT
    // NULL constraint and duplicated retrieval logic.
    public function up(): void
    {
        Schema::table('curriculum_exemplars', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('curriculum_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_exemplars', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });
    }
};
