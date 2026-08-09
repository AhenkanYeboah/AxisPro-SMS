<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Curricula are PLATFORM-level reference data, not school-owned - "GES"
    // and "Cambridge" are standard frameworks every tenant shares, not
    // something each school defines for itself. This is why there's no
    // school_id here (contrast with class_levels/subjects, which sit below
    // this and ARE school-scoped). A school's relationship to a curriculum
    // is expressed separately via school_curricula (which ones it has
    // activated) and class_levels.curriculum_id (which curriculum a given
    // class actually teaches under).
    public function up(): void
    {
        Schema::create('curricula', function (Blueprint $table) {
            $table->id();

            // Short stable identifier used in code/config (e.g. seeding,
            // matching against uploaded syllabus documents) - not shown to
            // users directly. Uppercase by convention: GES, CAMBRIDGE, IB.
            $table->string('code', 30)->unique();

            $table->string('name', 100); // e.g. "Ghana Education Service (GES/NaCCA)"
            $table->text('description')->nullable();

            // The grade/class naming convention this curriculum uses, so
            // UIs can render "Basic 4" vs "Year 4" vs "Grade 4" correctly
            // when a school sets up class_levels under this curriculum.
            // Purely a display hint - class_levels.name is still freetext.
            $table->string('grade_naming_convention', 50)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed the two curricula this platform actually deals with today.
        // Placed here (like the schools-table seed row) so a bare
        // `php artisan migrate` leaves a usable database without depending
        // on the seeder running separately.
        DB::table('curricula')->insert([
            [
                'code' => 'GES',
                'name' => 'Ghana Education Service (GES/NaCCA)',
                'description' => 'The national curriculum for Ghanaian schools, developed by the National Council for Curriculum and Assessment (NaCCA). Covers KG through SHS.',
                'grade_naming_convention' => 'Basic 1-9, SHS 1-3',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CAMBRIDGE',
                'name' => 'Cambridge International',
                'description' => 'Cambridge Primary, Lower Secondary, IGCSE, and A Level curricula from Cambridge International Education.',
                'grade_naming_convention' => 'Year 1-13',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('curricula');
    }
};
