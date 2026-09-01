<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchRequest extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'class_level_id', 'subject_id', 'topic', 'material_type',
        'source_chunk_ids', 'assigned_dok_levels',
        'generated_content', 'status', 'error_message', 'marked_helpful', 'promoted_to_exemplar_id',
    ];

    protected function casts(): array
    {
        return [
            'source_chunk_ids' => 'array',
            'assigned_dok_levels' => 'array',
            'marked_helpful' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function promotedToExemplar(): BelongsTo
    {
        return $this->belongsTo(CurriculumExemplar::class, 'promoted_to_exemplar_id');
    }

    public function sourceChunks()
    {
        return CurriculumDocumentChunk::whereIn('id', $this->source_chunk_ids ?? []);
    }

    // "DOK 2, 3, 4" - a request can legitimately span several levels at
    // once (see the assigned_dok_levels migration), so this is a sorted
    // list, not a single value.
    public function dokLevelsLabel(): string
    {
        $levels = collect($this->assigned_dok_levels ?? [])->sort()->values();

        return $levels->isEmpty() ? '' : 'DOK '.$levels->implode(', ');
    }
}
