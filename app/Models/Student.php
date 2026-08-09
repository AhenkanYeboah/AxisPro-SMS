<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'profile_image', 'first_name', 'middle_name', 'last_name', 'email',
        'date_of_birth', 'gender', 'phone', 'address', 'region', 'district',
        'next_of_kin', 'parent_name', 'parent_phone', 'parent_email', 'class', 'class_level_id', 'admission_status',
        'student_id', 'password', 'exam_date', 'exam_id', 'exam_completed',
        'exam_verified', 'status', 'results_file',
    ];

    protected $hidden = [
        'password',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'exam_date' => 'date',
            'exam_completed' => 'boolean',
            'exam_verified' => 'boolean',
        ];
    }

    // Students log in with their student_id, not email - Laravel's auth guard needs
    // to know this. See config/auth.php "username" for the student guard provider.

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    // Classes that require a results file upload at registration time.
    public static function classesRequiringResults(): array
    {
        return ['Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JHS 1', 'JHS 2'];
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function examSubmission(): HasOne
    {
        return $this->hasOne(ExamSubmission::class);
    }

    public function studentFees(): HasMany
    {
        return $this->hasMany(StudentFee::class);
    }

    public function noticeRecipients(): HasMany
    {
        return $this->hasMany(NoticeRecipient::class);
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    /**
     * Fee/notice delivery should reach a parent/guardian, not a young
     * child. parent_email/parent_phone are preferred when set; falls back
     * to the student's own email/phone only if no parent contact is on
     * file (e.g. an older JHS student who genuinely has their own, or a
     * record that predates the parent-contact fields being added).
     */
    public function contactEmail(): ?string
    {
        return $this->parent_email ?: $this->email ?: null;
    }

    public function contactPhone(): ?string
    {
        return $this->parent_phone ?: $this->phone ?: null;
    }
}
