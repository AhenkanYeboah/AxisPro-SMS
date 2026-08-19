<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * You (the platform owner) - NOT a school. Deliberately does not use the
 * BelongsToSchool trait: platform admins must be able to see and manage
 * every school, so this model is intentionally exempt from tenant scoping.
 */
class PlatformAdmin extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
