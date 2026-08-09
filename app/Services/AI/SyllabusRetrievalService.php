<?php

namespace App\Services\AI;

use App\Models\ClassLevel;
use App\Models\CurriculumDocumentChunk;
use App\Models\CurriculumExemplar;
use Illuminate\Support\Collection;

/**
 * Scopes and runs the retrieval step of the research assistant: given a
 * teacher's class_level + subject + topic, finds the syllabus chunks and
 * curated exemplars to ground generation in. This is the piece that makes
 * "not hallucinating" concrete - see TeachingMaterialGenerationService for
 * how the result gets turned into a prompt.
 */
class SyllabusRetrievalService
{
    private const MAX_CHUNKS = 6;
    private const MAX_EXEMPLARS = 2;

    /**
     * Translates a class name/tag - whether it's a ClassLevel's own name
     * ("Primary 4", "JHS 1") or a raw tag string pulled from a syllabus
     * PDF header ("B7/JHS1", "B4") - into a single normalized Basic-
     * equivalent number, so both sides of a comparison speak the same
     * numbering system.
     *
     * This exists because a naive digit-boundary regex on the RAW text
     * isn't safe: a chunk's class_tag can be a composite string like
     * "B7/JHS1" that contains two DIFFERENT numbers glued together - the
     * Basic-equivalent (7) and JHS's own local count (1). "Primary 1"
     * also normalizes toward the digit "1", so a plain token match on
     * "B7/JHS1" would wrongly treat it as a Primary 1 match, when it's
     * actually JHS 1 (=Basic 7) - two unrelated classes ~6 years apart.
     * Checking "jhs"/"shs" prefixes BEFORE the bare "b"/"basic" pattern
     * below is what makes "B7/JHS1" resolve to 7 (via the jhs branch,
     * using JHS's own "1" + 6) rather than being misread by a generic
     * digit grab.
     */
    private function normalizeToBasicNumber(string $text): ?int
    {
        $text = strtolower($text);

        if (preg_match('/jhs\s*0*(\d{1,2})/', $text, $m)) {
            return 6 + (int) $m[1];
        }

        // SHS isn't part of NaCCA's continuous Basic 1-9 numbering at all -
        // offset well clear of it (rather than e.g. 9+n, which would
        // collide with JHS 3 = Basic 9) purely so SHS tags can never
        // accidentally equal a Basic/JHS number in this internal scheme.
        if (preg_match('/shs\s*0*(\d{1,2})/', $text, $m)) {
            return 100 + (int) $m[1];
        }

        if (preg_match('/(?:basic|\bb)\s*0*(\d{1,2})/', $text, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/primary\s*0*(\d{1,2})/', $text, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/(?:year|grade)\s*0*(\d{1,2})/', $text, $m)) {
            return (int) $m[1];
        }

        return null; // e.g. KG/Creche/Nursery - no Basic-numbering equivalent, filtering is skipped for these
    }

    public function findChunks(ClassLevel $classLevel, ?int $subjectId, string $topic): Collection
    {
        if (! $classLevel->curriculum_id) {
            return collect();
        }

        $classNumber = $this->normalizeToBasicNumber($classLevel->name);

        // Fetch a wider relevance-ranked candidate pool than we need, then
        // narrow to this class precisely in PHP (see
        // CurriculumDocumentChunk::scopeMatching for why the narrowing
        // isn't done as a DB-level LIKE). 4x the target is enough slack
        // for the class-number filter to still leave a full MAX_CHUNKS
        // after narrowing, without pulling the whole table.
        $candidates = CurriculumDocumentChunk::matching($classLevel->curriculum_id, $subjectId, $topic)
            ->orderByRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) DESC', [$topic])
            ->limit(self::MAX_CHUNKS * 4)
            ->get();

        if ($classNumber === null) {
            return $candidates->take(self::MAX_CHUNKS);
        }

        return $this->filterByClassNumber($candidates, $classNumber)->take(self::MAX_CHUNKS)->values();
    }

    // Precise matches (chunk's own class_tag normalizes to the same Basic-
    // equivalent number) first, in their original relevance order since
    // $candidates was already MATCH-score sorted; untagged chunks
    // (class_tag null - most content isn't inside a class-specific header
    // block) kept as a relevance-ordered fallback after that. A chunk
    // whose tag normalizes to a DIFFERENT number is dropped entirely -
    // that's the fix: previously a loose LIKE let those leak through.
    private function filterByClassNumber(Collection $candidates, int $classNumber): Collection
    {
        $matching = $candidates->filter(function ($chunk) use ($classNumber) {
            if (! $chunk->class_tag) {
                return false;
            }

            return $this->normalizeToBasicNumber($chunk->class_tag) === $classNumber;
        });

        $untagged = $candidates->filter(fn ($chunk) => ! $chunk->class_tag);

        return $matching->concat($untagged);
    }

    public function findExemplars(ClassLevel $classLevel, ?int $subjectId, string $materialType): Collection
    {
        if (! $classLevel->curriculum_id) {
            return collect();
        }

        // Two pools, queried together: this school's own local exemplars
        // (school_id = its id) plus the platform-curated shared ones
        // (school_id null) - see the curriculum_exemplars migration for
        // why they live in one table. orderByRaw pushes this school's own
        // rows first (its own conventions should win when both pools have
        // a match for the same material_type), falling back to the shared
        // bank otherwise.
        $query = CurriculumExemplar::where('curriculum_id', $classLevel->curriculum_id)
            ->where('material_type', $materialType)
            ->where(function ($q) use ($classLevel) {
                $q->whereNull('school_id')->orWhere('school_id', $classLevel->school_id);
            });

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        return $query
            ->orderByRaw('school_id IS NULL') // false (0) sorts before true (1) - this school's rows first
            ->latest()
            ->limit(self::MAX_EXEMPLARS)
            ->get();
    }
}
