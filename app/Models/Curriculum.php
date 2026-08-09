<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    // Platform-level reference data (GES, Cambridge) - deliberately does
    // NOT use BelongsToSchool. Every school shares the same curricula rows;
    // what varies per school is which ones it has activated (schools())
    // and which class_levels/subjects it actually uses under each.
    protected $fillable = [
        'code', 'name', 'description', 'grade_naming_convention', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_curricula');
    }

    public function classLevels(): HasMany
    {
        return $this->hasMany(ClassLevel::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
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
