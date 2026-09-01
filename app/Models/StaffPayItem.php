<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPayItem extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'staff_id', 'name', 'type', 'amount_pesewas',
        'percentage_of_basic', 'is_recurring', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'percentage_of_basic' => 'decimal:2',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Resolve this item's pesewas value against a given basic salary -
     * either the flat amount, or the percentage of that basic.
     */
    public function amountFor(int $basicSalaryPesewas): int
    {
        if ($this->percentage_of_basic !== null) {
            return (int) round($basicSalaryPesewas * ((float) $this->percentage_of_basic / 100));
        }

        return (int) ($this->amount_pesewas ?? 0);
    }
}
