<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The tables that carried a freetext `class` column before class_levels
    // existed. Every one of them gets scanned for distinct values so no
    // school's existing data silently loses its class assignment.
    private array $sourceTables = ['students', 'assignments', 'timetables', 'fee_items', 'notices'];

    // Recognised Ghanaian class names in teaching order, used only to give
    // backfilled class_levels a sensible sort_order so they list correctly
    // in the UI. Anything not in this list still gets backfilled - it just
    // sorts after everything recognised, at sort_order 900+ in the order
    // encountered, rather than blocking the migration on an unfamiliar name.
    private array $knownOrder = [
        'creche', 'nursery 1', 'nursery 2', 'kg 1', 'kg1', 'kg 2', 'kg2',
        'primary 1', 'primary 2', 'primary 3', 'primary 4', 'primary 5', 'primary 6',
        'basic 1', 'basic 2', 'basic 3', 'basic 4', 'basic 5', 'basic 6',
        'basic 7', 'basic 8', 'basic 9',
        'jhs 1', 'jhs 2', 'jhs 3',
        'shs 1', 'shs 2', 'shs 3',
        'year 1', 'year 2', 'year 3', 'year 4', 'year 5', 'year 6', 'year 7',
        'year 8', 'year 9', 'year 10', 'year 11', 'year 12', 'year 13',
    ];

    public function up(): void
    {
        // school_id => curriculum_id, only for schools with EXACTLY one
        // activated curriculum. A school with zero or multiple curricula
        // activated is one we genuinely can't guess for - its backfilled
        // class_levels get curriculum_id = null and are left for an admin
        // to assign manually in settings, rather than the migration
        // guessing wrong and silently mis-tagging every one of its classes.
        $singleCurriculumBySchool = DB::table('school_curricula')
            ->select('school_id', DB::raw('COUNT(*) as curriculum_count'), DB::raw('MIN(curriculum_id) as only_curriculum_id'))
            ->groupBy('school_id')
            ->havingRaw('COUNT(*) = 1')
            ->get()
            ->keyBy('school_id');

        $nextUnknownOrder = [];

        foreach ($this->sourceTables as $sourceTable) {
            $rows = DB::table($sourceTable)
                ->select('school_id', 'class')
                ->whereNotNull('class')
                ->where('class', '!=', '')
                ->distinct()
                ->get();

            foreach ($rows as $row) {
                $schoolId = $row->school_id;
                $className = trim($row->class);

                if ($className === '') {
                    continue;
                }

                // Already backfilled from an earlier source table in this
                // same run - skip re-inserting, just fall through to the
                // link step below.
                $existing = DB::table('class_levels')
                    ->where('school_id', $schoolId)
                    ->where('name', $className)
                    ->whereNull('section')
                    ->first();

                if (!$existing) {
                    $curriculumId = $singleCurriculumBySchool->get($schoolId)?->only_curriculum_id;

                    $normalised = strtolower($className);
                    $knownPosition = array_search($normalised, $this->knownOrder, true);

                    if ($knownPosition !== false) {
                        $sortOrder = $knownPosition;
                    } else {
                        $nextUnknownOrder[$schoolId] = ($nextUnknownOrder[$schoolId] ?? 900) + 1;
                        $sortOrder = $nextUnknownOrder[$schoolId];
                    }

                    DB::table('class_levels')->insert([
                        'school_id' => $schoolId,
                        'curriculum_id' => $curriculumId,
                        'name' => $className,
                        'section' => null,
                        'sort_order' => $sortOrder,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Link this source table's rows to the class_levels row that
            // now exists for their (school_id, class) pair. Done per-table
            // right after its own scan so we don't need to hold every
            // table's rows in memory at once.
       DB::statement(<<<SQL
UPDATE students AS src
SET class_level_id = cl.id
FROM class_levels AS cl
WHERE cl.school_id = src.school_id
  AND cl.name = TRIM(src.class)
  AND cl.section IS NULL
  AND src.class IS NOT NULL 
  AND src.class != ''
SQL);
        }
    }

    public function down(): void
    {
        // Unlinking (clearing class_level_id) and deleting the backfilled
        // class_levels rows is destructive to reconstruct exactly, and the
        // original `class` string columns were never touched - so the
        // source data isn't lost even without a down() here. Intentionally
        // left as a no-op; roll back the class_level_id-adding migrations
        // instead if this needs to be undone.
    }
};
