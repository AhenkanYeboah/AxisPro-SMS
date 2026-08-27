<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryMaterial extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'subject_id', 'class_level_id',
        'title', 'description', 'category',
        'file_path', 'file_type', 'file_size', 'allow_download',
        'uploaded_by_type', 'uploaded_by_admin_id', 'uploaded_by_teacher_id',
    ];

    protected function casts(): array
    {
        return [
            'allow_download' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    public function uploadedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'uploaded_by_admin_id');
    }

    public function uploadedByTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'uploaded_by_teacher_id');
    }

    public function uploaderName(): string
    {
        return $this->uploaded_by_type === 'admin'
            ? ($this->uploadedByAdmin?->full_name ?? 'School Admin')
            : ($this->uploadedByTeacher?->full_name ?? 'Teacher');
    }

    public function formattedSize(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}
