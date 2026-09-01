<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'payroll_run_id', 'staff_id', 'basic_salary_pesewas',
        'allowances_pesewas', 'gross_pay_pesewas', 'ssnit_employee_pesewas',
        'ssnit_employer_pesewas', 'paye_pesewas', 'other_deductions_pesewas',
        'net_pay_pesewas', 'breakdown',
    ];

    protected function casts(): array
    {
        return [
            'breakdown' => 'array',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
