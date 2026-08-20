<?php

namespace App\Models\Concerns;

use App\Models\PlatformAdmin;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    /**
     * Boot the trait to apply automatic school_id scoping and assignment.
     */
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $builder) {
            // Bypass global scope if caller is a PlatformAdmin and no specific school context is bound
            if (auth()->check() && auth()->user() instanceof PlatformAdmin && !app()->has('currentSchool')) {
                return;
            }

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
     * Helper to resolve the current active school safely across HTTP, API, and CLI.
     */
    protected static function resolveCurrentSchool()
    {
        // 1. Highest Priority: App container binding (Middleware or Job context)
        if (app()->has('currentSchool')) {
            return app('currentSchool');
        }

        // 2. Direct user property check (if user model has direct school_id relation)
        if (auth()->check() && isset(auth()->user()->school_id)) {
            return (object) ['id' => auth()->user()->school_id];
        }

        // 3. Session fallback (safely checked only if session service exists)
        if (app()->bound('session') && session()->has('active_school_id')) {
            return (object) ['id' => session('active_school_id')];
        }

        return null;
    }

    /**
     * Local scope to explicitly query without tenant isolation when needed.
     */
    public function scopeWithoutSchoolScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('school');
    }

    /**
     * Relationship back to the School model.
     */
    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }
}
