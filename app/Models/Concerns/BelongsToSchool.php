<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    /**
     * Boot the trait to apply automatic school_id scoping and assignment.
     */
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $builder) {
            $currentSchool = static::resolveCurrentSchool();

            // Only apply tenant filter if a valid school instance exists
            if ($currentSchool && isset($currentSchool->id)) {
                $builder->where($builder->getModel()->getTable() . '.school_id', $currentSchool->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->school_id) {
                $currentSchool = static::resolveCurrentSchool();

                if ($currentSchool && isset($currentSchool->id)) {
                    $model->school_id = $currentSchool->id;
                }
            }
        });
    }

    /**
     * Helper to resolve the current active school from Container or Session.
     */
    protected static function resolveCurrentSchool()
    {
        if (app()->has('currentSchool')) {
            return app('currentSchool');
        }

        if (session()->has('active_school_id')) {
            return (object) ['id' => session('active_school_id')];
        }

        return null;
    }
}
