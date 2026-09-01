<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'assignment_id', 'student_id', 'submission_file', 'marks', 'feedback', 'status',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'marks' => 'decimal:2',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
