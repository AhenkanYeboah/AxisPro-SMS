<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id','staff_code','full_name','phone','email',
        'designation','employment_type','employment_status',
        'teacher_id','bank_name','bank_branch','account_number','mobile_money_number',
        'ssnit_number','tin_number','hired_at'
    ];

    protected $casts = [
        'hired_at' => 'date',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function salaryStructures()
    {
        return $this->hasMany(SalaryStructure::class);
    }

    public function activeSalaryStructure()
    {
        return $this->hasOne(SalaryStructure::class)->where('is_active', true)->latestOfMany('effective_from');
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }
}
