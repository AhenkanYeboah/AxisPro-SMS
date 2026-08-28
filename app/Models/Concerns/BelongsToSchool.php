<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $builder) {
            // Only scope when currentSchool is bound by middleware
            if (app()->bound('currentSchool')) {
                $school = app('currentSchool');
                if ($school && isset($school->id)) {
                    $builder->where($builder->getModel()->getTable() . '.school_id', $school->id);
                }
            }
        });

        static::creating(function ($model) {
            if (empty($model->school_id) && app()->bound('currentSchool')) {
                $school = app('currentSchool');
                if ($school && isset($school->id)) {
                    $model->school_id = $school->id;
                }
            }
        });
    }

    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }
}
