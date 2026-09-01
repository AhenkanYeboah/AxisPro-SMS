<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedBigInteger('basic_salary_pesewas');
            $table->unsignedBigInteger('allowances_pesewas')->default(0);
            $table->unsignedBigInteger('gross_pay_pesewas');
            $table->unsignedBigInteger('ssnit_employee_pesewas')->default(0);
            $table->unsignedBigInteger('ssnit_employer_pesewas')->default(0); // informational - employer cost, not deducted from net
            $table->unsignedBigInteger('paye_pesewas')->default(0);
            $table->unsignedBigInteger('other_deductions_pesewas')->default(0);
            $table->unsignedBigInteger('net_pay_pesewas');
            $table->json('breakdown')->nullable(); // itemized allowance/deduction line items as they stood at run time
            $table->timestamps();

            $table->unique(['payroll_run_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
