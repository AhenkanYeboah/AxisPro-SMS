<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualClass extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'class_level_id', 'subject_id', 'title',
        'platform', 'join_url', 'zoom_meeting_id', 'host_notes',
        'scheduled_start', 'scheduled_end', 'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(VirtualClassAttendance::class);
    }

    // "Live" isn't a stored status - it's derived from the current time
    // falling inside the scheduled window, so it's always correct without
    // needing a scheduled job to flip a status column at the right
    // moment. 'ended'/'cancelled' are the only states that need explicit
    // action; everything else is just "has scheduled_start passed yet".
    public function isLive(): bool
    {
        return $this->status === 'scheduled'
            && now()->between($this->scheduled_start, $this->scheduled_end);
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_start->isFuture();
    }

    public function isPast(): bool
    {
        return $this->status !== 'cancelled' && $this->scheduled_end->isPast();
    }
}
