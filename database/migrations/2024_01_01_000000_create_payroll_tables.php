<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Staff — ONLY create if not exists (already exists on Render)
        if (!Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->string('staff_code', 20)->nullable()->unique();
                $table->string('full_name', 150);
                $table->string('phone', 20)->nullable();
                $table->string('email', 150)->nullable();
                $table->enum('designation', ['teacher','admin','accountant','support','driver','cook','security','cleaner','other'])->default('teacher');
                $table->enum('employment_type', ['full_time','part_time','contract','national_service'])->default('full_time');
                $table->enum('employment_status', ['active','on_leave','terminated'])->default('active');
                $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
                $table->string('bank_name', 100)->nullable();
                $table->string('bank_branch', 100)->nullable();
                $table->string('account_number', 50)->nullable();
                $table->string('mobile_money_number', 20)->nullable();
                $table->string('ssnit_number', 50)->nullable();
                $table->string('tin_number', 50)->nullable();
                $table->date('hired_at')->nullable();
                $table->timestamps();
                $table->index(['school_id', 'employment_status']);
            });
        }

        // 2. Salary structures
        if (!Schema::hasTable('salary_structures')) {
            Schema::create('salary_structures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->bigInteger('basic_pesewas');
                $table->json('allowances_json')->nullable();
                $table->json('deductions_json')->nullable();
                $table->boolean('is_active')->default(true);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->timestamps();
            });
        }

        // 3. PAYE brackets
        if (!Schema::hasTable('paye_tax_brackets')) {
            Schema::create('paye_tax_brackets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
                $table->bigInteger('lower_pesewas');
                $table->bigInteger('upper_pesewas')->nullable();
                $table->decimal('rate_percent', 5, 2);
                $table->integer('sort_order');
                $table->boolean('is_active')->default(true);
                $table->integer('year')->default(2024);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ssnit_settings')) {
            Schema::create('ssnit_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('employee_rate', 5, 2)->default(5.50);
                $table->decimal('employer_rate', 5, 2)->default(13.00);
                $table->decimal('tier2_rate', 5, 2)->default(5.00);
                $table->boolean('is_active')->default(true);
                $table->integer('year')->default(2024);
                $table->timestamps();
            });
        }

        // 4. Payroll runs
        if (!Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->tinyInteger('month');
                $table->smallInteger('year');
                $table->enum('status', ['draft','prepared','approved','paid','cancelled'])->default('draft');
                $table->bigInteger('total_gross_pesewas')->default(0);
                $table->bigInteger('total_employee_ssnit_pesewas')->default(0);
                $table->bigInteger('total_employer_ssnit_pesewas')->default(0);
                $table->bigInteger('total_paye_pesewas')->default(0);
                $table->bigInteger('total_net_pesewas')->default(0);
                $table->foreignId('prepared_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['school_id','month','year']);
            });
        }

        if (!Schema::hasTable('payroll_items')) {
            Schema::create('payroll_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->bigInteger('basic_pesewas');
                $table->bigInteger('allowances_pesewas')->default(0);
                $table->bigInteger('gross_pesewas');
                $table->bigInteger('ssnit_employee_pesewas')->default(0);
                $table->bigInteger('ssnit_employer_pesewas')->default(0);
                $table->bigInteger('taxable_pesewas');
                $table->bigInteger('paye_pesewas')->default(0);
                $table->bigInteger('other_deductions_pesewas')->default(0);
                $table->bigInteger('net_pesewas');
                $table->json('snapshot_json')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payslips')) {
            Schema::create('payslips', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payroll_item_id')->unique()->constrained('payroll_items')->cascadeOnDelete();
                $table->string('pdf_path')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('ssnit_settings');
        Schema::dropIfExists('paye_tax_brackets');
        Schema::dropIfExists('salary_structures');
        // Don't drop staff on down — it existed before payroll
    }
};
