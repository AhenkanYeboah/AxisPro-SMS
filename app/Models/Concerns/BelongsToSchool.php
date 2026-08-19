<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $builder) {
            // Skip scope completely if running CLI/Seeders or if currentSchool is not bound
            if (app()->runningInConsole()) {
                return;
            }

            $currentSchool = app()->has('currentSchool') ? app('currentSchool') : null;

            if ($currentSchool && isset($currentSchool->id)) {
                $builder->where($builder->getModel()->getTable() . '.school_id', $currentSchool->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->school_id && app()->has('currentSchool')) {
                $currentSchool = app('currentSchool');
                if ($currentSchool && isset($currentSchool->id)) {
                    $model->school_id = $currentSchool->id;
                }
            }
        });
    }
}
