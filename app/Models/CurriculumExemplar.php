<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumExemplar extends Model
{
    // NOT BelongsToSchool, on purpose - that trait's global scope would
    // silently filter out the platform-shared rows (school_id = null)
    // whenever a school context is bound, which is the opposite of what
    // retrieval needs (see SyllabusRetrievalService::findExemplars, which
    // deliberately queries BOTH null and this-school rows together).
    // Scoping to "this school's own rows only" is done explicitly wherever
    // that's actually the intent (AdminExemplarController), not implicitly
    // via a trait.
    protected $fillable = [
        'curriculum_id', 'school_id', 'subject_id', 'dok_level_id', 'class_tag',
        'title', 'material_type', 'content', 'approved_by_platform_admin_id',
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function dokLevel(): BelongsTo
    {
        return $this->belongsTo(DokLevel::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class, 'approved_by_platform_admin_id');
    }
}
