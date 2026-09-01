<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Non-statutory recurring pay components: housing/transport/other
        // allowances that add to gross pay, and deductions like a salary
        // advance repayment or a uniform cost. Statutory SSNIT/PAYE are
        // NOT stored here - they're computed fresh every run from
        // payroll_tax_bands / payroll_settings so a tax-law change doesn't
        // require touching every staff record.
        Schema::create('staff_pay_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('name', 100); // e.g. "Housing Allowance", "Salary Advance Repayment"
            $table->enum('type', ['allowance', 'deduction']);
            $table->unsignedBigInteger('amount_pesewas')->nullable(); // fixed amount, mutually exclusive with percentage_of_basic
            $table->decimal('percentage_of_basic', 5, 2)->nullable(); // e.g. 10.00 = 10% of basic salary
            $table->boolean('is_recurring')->default(true); // false = applies to the next run only, then admin deactivates it
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'staff_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_pay_items');
    }
};
