<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumDocumentChunk extends Model
{
    protected $fillable = [
        'curriculum_document_id', 'curriculum_id', 'subject_id', 'class_tag',
        'strand', 'sub_strand', 'indicator_code', 'chunk_index', 'page_number', 'content',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(CurriculumDocument::class, 'curriculum_document_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // Keyword-scored retrieval, scoped to a curriculum + subject and
    // loosely to a class tag (LIKE, not =, since a school's class name -
    // e.g. "Primary 4" - won't match a syllabus's own marker - e.g.
    // "B4" - exactly; see the migration comment on class_tag). This is
    // Phase 1 retrieval: MySQL FULLTEXT, not vector similarity - see the
    // chunks migration for why that's a deliberate, swappable choice
    // rather than a placeholder.
    public function scopeMatching(Builder $query, int $curriculumId, ?int $subjectId, ?string $classTag, string $searchTerms): Builder
    {
        $query->where('curriculum_id', $curriculumId)
            ->whereFullText('content', $searchTerms);

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        if ($classTag) {
            // Match on the leading class token (e.g. "B7" out of "B7/JHS1")
            // so "Primary 4" scoped requests can still find a document
            // tagged "B4" once an admin has recorded that equivalence -
            // see ClassLevel/curriculum mapping notes in the retrieval
            // service for how that loose match gets narrowed further.
            $query->where(function ($q) use ($classTag) {
                $q->where('class_tag', 'like', "%{$classTag}%")
                    ->orWhereNull('class_tag');
            });
        }

        return $query;
    }
}
