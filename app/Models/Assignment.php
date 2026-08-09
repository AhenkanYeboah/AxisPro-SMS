<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'class', 'class_level_id', 'title', 'description', 'due_date', 'file_path',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    // Mirrors the original's `strtotime($due_row['due_date']) < time()` check
    // used to block late submissions.
    public function isPastDue(): bool
    {
        return $this->due_date !== null && $this->due_date->endOfDay()->isPast();
    }
}
