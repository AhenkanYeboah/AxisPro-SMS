<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invite extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'code', 'type', 'email', 'created_by_admin_id', 'expires_at',
        'used_at', 'used_by_admin_id', 'used_by_teacher_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function usedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'used_by_admin_id');
    }

    public function usedByTeacher()
    {
        return $this->belongsTo(Teacher::class, 'used_by_teacher_id');
    }

    // Generates a short, human-typeable code, e.g. "K7M2-QX9P".
    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(4).'-'.Str::random(4));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function isValid(?string $forEmail = null): bool
    {
        if ($this->used_at) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->email && $forEmail && strcasecmp($this->email, $forEmail) !== 0) {
            return false;
        }

        return true;
    }

    public function markUsedByAdmin(Admin $admin): void
    {
        $this->update(['used_at' => now(), 'used_by_admin_id' => $admin->id]);
    }

    public function markUsedByTeacher(Teacher $teacher): void
    {
        $this->update(['used_at' => now(), 'used_by_teacher_id' => $teacher->id]);
    }
}
