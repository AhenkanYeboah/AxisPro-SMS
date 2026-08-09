<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Subjects belong to a CURRICULUM, not a school - GES's subject list
    // and Cambridge's subject list genuinely differ (this is the whole
    // point of the curriculum model), so a subject is platform-level
    // reference data like curricula itself, seeded once and shared by
    // every school running that curriculum. A school doesn't invent its
    // own "Science" - it picks a curriculum and inherits that curriculum's
    // subjects. (Room is left for school-specific electives later, but
    // that's a distinct, additive table - not a reason to make every
    // subject school-owned now.)
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20)->nullable(); // e.g. short form for timetable grids
            $table->timestamps();

            $table->unique(['curriculum_id', 'name']);
        });

        $this->seedGesSubjects();
        $this->seedCambridgeSubjects();
    }

    // Core GES/NaCCA Common Core subject list (Basic + JHS level, per the
    // curriculum's own "Learning Areas" list). SHS-specific subjects
    // (Government, Literature-in-English, Applied Technology, etc.) are
    // deliberately left out of this seed - RCA, the only live GES school
    // right now, doesn't run SHS, and adding a large SHS subject list here
    // would be guessing at a scope nobody's asked for yet.
    private function seedGesSubjects(): void
    {
        $gesId = DB::table('curricula')->where('code', 'GES')->value('id');

        if (!$gesId) {
            return;
        }

        $subjects = [
            'English Language', 'Mathematics', 'Science', 'Computing',
            'Social Studies', 'Religious and Moral Education', 'Creative Arts and Design',
            'Career Technology', 'Physical and Health Education', 'Ghanaian Language',
            'French',
        ];

        $this->insertSubjects($gesId, $subjects);
    }

    // Minimal Cambridge Primary/Lower Secondary subject list - the
    // curriculum most schools would realistically run alongside GES.
    // Intentionally a starter set, not exhaustive (no IGCSE-specific
    // subjects yet) - expand when a school actually activates Cambridge.
    private function seedCambridgeSubjects(): void
    {
        $cambridgeId = DB::table('curricula')->where('code', 'CAMBRIDGE')->value('id');

        if (!$cambridgeId) {
            return;
        }

        $subjects = ['English', 'Mathematics', 'Science', 'Global Perspectives', 'ICT Starters'];

        $this->insertSubjects($cambridgeId, $subjects);
    }

    private function insertSubjects(int $curriculumId, array $names): void
    {
        $rows = array_map(fn ($name) => [
            'curriculum_id' => $curriculumId,
            'name' => $name,
            'code' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $names);

        DB::table('subjects')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
