<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notice extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'sent_by_admin_id', 'title', 'body',
        'audience', 'class', 'class_level_id', 'channel', 'status',
    ];

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sent_by_admin_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NoticeRecipient::class);
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    /**
     * Resolves which students this notice targets, based on audience/class.
     * Used both to build notice_recipients rows at send time and to show
     * an admin a live count while composing.
     */
    public function targetStudents()
    {
        $query = Student::query();

        if ($this->audience === 'class' && $this->class) {
            $query->where('class', $this->class);
        }

        // 'individual' audience is handled separately at compose time (a
        // specific student is picked) rather than derived here, since
        // there's no student_id column on notices itself - see
        // AdminNoticeController::store().

        return $query->get();
    }
}
