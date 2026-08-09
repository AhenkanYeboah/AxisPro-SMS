<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A school can run more than one curriculum at once (confirmed
    // requirement - e.g. GES for the main school + a separate Cambridge
    // track), so this is a many-to-many pivot rather than a single
    // curriculum_id column on schools. This table answers "which
    // curricula has this school activated / can it use at signup or in
    // settings" - it does NOT say which class teaches which curriculum;
    // that's class_levels.curriculum_id, one level down.
    public function up(): void
    {
        Schema::create('school_curricula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'curriculum_id']);
        });

        // RCA (school_id = 1, seeded in the schools-table migration) is a
        // GES school in practice - backfill that here so the class_levels
        // backfill migration right after this one has a curriculum to
        // attach RCA's existing classes to.
        if (DB::table('schools')->where('id', 1)->exists()) {
            $gesId = DB::table('curricula')->where('code', 'GES')->value('id');

            if ($gesId) {
                DB::table('school_curricula')->insert([
                    'school_id' => 1,
                    'curriculum_id' => $gesId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_curricula');
    }
};
