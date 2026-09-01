<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollSetting extends Model
{
    protected $fillable = [
        'school_id', 'ssnit_employee_rate', 'ssnit_employer_rate',
        'ssnit_ceiling_pesewas', 'pay_day_of_month', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'ssnit_employee_rate' => 'decimal:2',
            'ssnit_employer_rate' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get (or lazily create with defaults) the settings row for a school,
     * so the calculator always has somewhere to read rates from without
     * every controller needing to null-check.
     */
    public static function forSchool(int $schoolId): self
    {
        return static::firstOrCreate(['school_id' => $schoolId]);
    }
}
