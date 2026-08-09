<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'title', 'instructions', 'questions', 'file_path', 'file_original_name', 'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ExamSubmission::class);
    }

    public function assignedStudents(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
