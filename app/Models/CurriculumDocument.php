<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumDocument extends Model
{
    protected $fillable = [
        'curriculum_id', 'subject_id', 'title', 'file_path', 'source_url',
        'document_type', 'ingestion_status', 'ingestion_error', 'uploaded_by_platform_admin_id',
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(CurriculumDocumentChunk::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class, 'uploaded_by_platform_admin_id');
    }
}
