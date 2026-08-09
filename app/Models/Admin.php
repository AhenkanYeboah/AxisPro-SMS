<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use BelongsToSchool;

    // Laravel assumes table name "admins" automatically from the class name - no need to declare it.

    protected $fillable = [
        'school_id', 'admin_id', 'username', 'email', 'password', 'full_name', 'role',
    ];

    protected $hidden = [
        'password',
    ];

    public $timestamps = false; // this table only has created_at, not updated_at

    protected function casts(): array
    {
        return [
            'password' => 'hashed', // Laravel auto-hashes on save, replacing password_hash() calls
        ];
    }
}
