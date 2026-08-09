<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeRecipient extends Model
{
    // Deliberately no BelongsToSchool - always reached through its parent
    // Notice, which is itself school-scoped. See migration comment.

    protected $fillable = [
        'notice_id', 'student_id', 'email_status', 'sms_status', 'error_message',
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
