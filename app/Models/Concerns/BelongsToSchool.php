<?php

namespace App\Models\Concerns;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Applied to every model that belongs to a single school (Admin, Teacher,
 * Student, Exam, Invite, Assignment, Attendance, ReportCard, Timetable,
 * SchoolActivity, ExamSubmission, AssignmentSubmission).
 *
 * This is the safety net for multi-tenancy: instead of relying on every
 * controller remembering to add ->where('school_id', ...) to every query
 * (one missed line = one school seeing another school's data), this trait
 * makes it automatic and global:
 *
 *   - Every query against the model is silently scoped to app('currentSchool')
 *   - Every new record silently gets school_id filled in on create
 *
 * A query only sees a different school's rows if it deliberately opts out
 * with Model::withoutGlobalScope('school') - so a leak has to be an
 * intentional, visible decision, not an accident.
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $builder) {
            if (app()->bound('currentSchool')) {
                $builder->where($builder->getModel()->getTable().'.school_id', app('currentSchool')->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->school_id && app()->bound('currentSchool')) {
                $model->school_id = app('currentSchool')->id;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
