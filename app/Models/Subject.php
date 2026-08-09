<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    // Not BelongsToSchool - subjects belong to a curriculum (platform-level
    // reference data), not to an individual school. See the migration
    // comment on why: a school inherits its subject list from the
    // curriculum(s) it activates rather than defining its own.
    protected $fillable = [
        'curriculum_id', 'name', 'code',
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CurriculumDocument::class);
    }

    public function exemplars(): HasMany
    {
        return $this->hasMany(CurriculumExemplar::class);
    }
}
