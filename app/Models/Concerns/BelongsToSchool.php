<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        // READ-ONLY scope - never call auth() here, only container binding
        static::addGlobalScope('school', function (Builder $builder) {
            // If currentSchool is bound (by ResolveTenant), filter
            // If not bound, DON'T filter - let query run unscoped
            // This prevents crash during login when school not yet resolved
            if (app()->has('currentSchool')) {
                $currentSchool = app('currentSchool');
                if ($currentSchool && isset($currentSchool->id)) {
                    $builder->where($builder->getModel()->getTable() . '.school_id', $currentSchool->id);
                }
            }
        });

        static::creating(function ($model) {
            if (empty($model->school_id)) {
                // Only use container binding, never auth() during creation to avoid recursion
                if (app()->has('currentSchool')) {
                    $school = app('currentSchool');
                    if ($school && isset($school->id)) {
                        $model->school_id = $school->id;
                    }
                } elseif (app()->bound('session') && session()->has('active_school_id')) {
                    $model->school_id = session('active_school_id');
                }
                // NOTE: school_id should also be set explicitly in controllers
                // as fallback - don't rely solely on this
            }
        });
    }

    public function scopeWithoutSchoolScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('school');
    }

    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }
}
