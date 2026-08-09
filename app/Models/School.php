<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name', 'subdomain', 'logo_path', 'primary_color', 'tagline', 'phone', 'contact_email',
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

    // The raw `status` column can lag reality: a school stays 'active' in
    // the database even after subscription_ends_at quietly passes, because
    // nothing on hosts like InfinityFree runs a cron to flip it. isActive()
    // already accounts for that when deciding whether to actually let a
    // school in - this does the same for what gets DISPLAYED, so the
    // platform dashboard can't show "Active" for a school that would
    // currently be locked out.
    public function displayStatus(): string
    {
        if ($this->status === 'suspended') {
            return 'suspended';
        }

        if (!$this->isActive()) {
            return 'expired';
        }

        return $this->status; // 'trial' or 'active', and genuinely is
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
            // 'active' means they've paid at least once. Still gate on the
            // subscription actually being current - otherwise a school could
            // pay once and stay "active" forever after their term lapses.
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

    // Curricula this school has activated (GES, Cambridge, or both) -
    // chosen at signup, editable later in settings. See school_curricula
    // migration for why this is many-to-many rather than a single column.
    public function curricula(): BelongsToMany
    {
        return $this->belongsToMany(Curriculum::class, 'school_curricula');
    }

    public function classLevels(): HasMany
    {
        return $this->hasMany(ClassLevel::class);
    }
}
