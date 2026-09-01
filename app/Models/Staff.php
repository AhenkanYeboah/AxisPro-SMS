<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    use BelongsToSchool;

    protected $table = 'staff';

    protected $fillable = [
        'school_id', 'staff_no', 'full_name', 'email', 'phone', 'position', 'department',
        'employment_type', 'date_joined', 'date_left', 'bank_name', 'bank_account_number',
        'bank_account_name', 'mobile_money_provider', 'mobile_money_number',
        'teacher_id', 'admin_id', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_joined' => 'date',
            'date_left' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class);
    }

    public function payItems(): HasMany
    {
        return $this->hasMany(StaffPayItem::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * The salary structure currently in effect (latest effective_from
     * that hasn't been superseded by a later one).
     */
    public function currentSalaryStructure(): ?SalaryStructure
    {
        return $this->salaryStructures()
            ->whereNull('effective_to')
            ->orderByDesc('effective_from')
            ->first();
    }
}
