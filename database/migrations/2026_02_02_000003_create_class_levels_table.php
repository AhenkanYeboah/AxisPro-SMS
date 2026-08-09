<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Until now "class" has been a bare freetext string duplicated across
    // students/assignments/timetables/fee_items/notices (5 tables, 5
    // sources of truth, no way to know what curriculum a class follows).
    // This table replaces that: one real row per class/section, scoped to
    // a school, pointing at the curriculum it's taught under. The
    // migrations right after this one add class_level_id FKs to those 5
    // tables and backfill them from the existing strings.
    public function up(): void
    {
        Schema::create('class_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // Nullable, not required: a class can exist before a school
            // finishes picking curricula (e.g. mid-signup), and the
            // backfill migration may not always be able to infer one
            // confidently (see that migration's comments).
            $table->foreignId('curriculum_id')->nullable()->constrained('curricula')->nullOnDelete();

            // Freetext display name, matching whatever a school already
            // calls its classes - "Primary 4", "Basic 4", "Year 4",
            // "JHS 1" all stay as-is rather than being forced into GES's
            // Basic-1-to-9 naming. curriculum_id is what carries the
            // actual GES/Cambridge meaning; this is just the label.
            $table->string('name', 50);

            // Optional stream/section within a class level, e.g. "A"/"B" -
            // this is what actually allows a school to run two curricula
            // side by side within the "same" grade (e.g. "Year 7 - Cambridge"
            // vs "Basic 7 - GES" as two distinct rows, or "JHS 1A" / "JHS 1B"
            // as two streams of the same curriculum).
            $table->string('section', 20)->nullable();

            // Display/progression order (KG < Primary 1 < ... < SHS 3),
            // since "name" alone doesn't sort correctly as a string.
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['school_id', 'name', 'section'], 'unique_school_class_section');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_levels');
    }
};
