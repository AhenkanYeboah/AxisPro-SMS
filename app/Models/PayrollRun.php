<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'period_month', 'period_year', 'status',
        'prepared_by_admin_id', 'approved_by_admin_id', 'approved_at', 'paid_at',
        'total_gross_pesewas', 'total_ssnit_employee_pesewas', 'total_paye_pesewas',
        'total_other_deductions_pesewas', 'total_net_pesewas', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'prepared_by_admin_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function periodLabel(): string
    {
        return \Carbon\Carbon::create($this->period_year, $this->period_month, 1)->format('F Y');
    }
}
