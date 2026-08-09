<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // `teachers.assigned_class` was missed in the original class_level_id
    // rollout (...000004 through ...000009) - it wasn't in the list of
    // tables that had a `class` column at the time, but it does, and the
    // research assistant needs it: a teacher's class_level_id is how we
    // know which curriculum (and therefore which syllabus documents) to
    // ground their generated material in. Fixing that gap here rather
    // than leaving it inconsistent with every other class-bearing table.
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('class_level_id')->nullable()->after('assigned_class')->constrained('class_levels')->nullOnDelete();
        });

        // Same backfill logic as ...000009, scoped to this one table.
        // class_levels rows already exist for most of these class names
        // (created from students/assignments/etc.) - this only inserts a
        // NEW class_levels row on the rare chance a teacher is assigned to
        // a class name that doesn't already appear anywhere else.
        $rows = DB::table('teachers')
            ->select('school_id', 'assigned_class')
            ->whereNotNull('assigned_class')
            ->where('assigned_class', '!=', '')
            ->distinct()
            ->get();

        foreach ($rows as $row) {
            $className = trim($row->assigned_class);

            if ($className === '') {
                continue;
            }

            $exists = DB::table('class_levels')
                ->where('school_id', $row->school_id)
                ->where('name', $className)
                ->whereNull('section')
                ->exists();

            if (!$exists) {
                $curriculumId = DB::table('school_curricula')
                    ->where('school_id', $row->school_id)
                    ->pluck('curriculum_id');

                DB::table('class_levels')->insert([
                    'school_id' => $row->school_id,
                    'curriculum_id' => $curriculumId->count() === 1 ? $curriculumId->first() : null,
                    'name' => $className,
                    'section' => null,
                    'sort_order' => 900,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::statement(<<<'SQL'
            UPDATE teachers AS t
            JOIN class_levels AS cl
              ON cl.school_id = t.school_id
             AND cl.name = TRIM(t.assigned_class)
             AND cl.section IS NULL
            SET t.class_level_id = cl.id
            WHERE t.assigned_class IS NOT NULL AND t.assigned_class != ''
        SQL);
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_level_id');
        });
    }
};
