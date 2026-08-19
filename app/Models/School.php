<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name', 'slug', 'subdomain', 'logo_path', 'primary_color', 'tagline', 'phone', 'contact_email',
        'status', 'trial_ends_at', 'plan', 'subscription_ends_at',
        'paystack_customer_code', 'paystack_subscription_code',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function displayStatus(): string
    {
        if ($this->status === 'suspended') {
            return 'suspended';
        }

        if (!$this->isActive()) {
            return 'expired';
        }

        return $this->status;
    }

    public function isActive(): bool
    {
        if ($this->status === 'suspended') {
            return false;
        }

        if ($this->status === 'trial') {
            return !$this->trial_ends_at || $this->trial_ends_at->isFuture();
        }

        if ($this->status === 'active') {
            return !$this->subscription_ends_at || $this->subscription_ends_at->isFuture();
        }

        return false;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function curricula(): BelongsToMany
    {
        return $this->belongsToMany(Curriculum::class, 'school_curricula');
    }

    public function classLevels(): HasMany
    {
        return $this->hasMany(ClassLevel::class);
    }
}
