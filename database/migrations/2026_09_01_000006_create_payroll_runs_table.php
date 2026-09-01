<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per school per pay period. Deliberately has an approval
        // step (draft -> pending_approval -> approved -> paid) rather than
        // firing payslips the moment it's generated - payroll is one of
        // the few things in this app that should never be a single click.
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('period_month'); // 1-12
            $table->unsignedSmallInteger('period_year');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->foreignId('prepared_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('approved_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('total_gross_pesewas')->default(0);
            $table->unsignedBigInteger('total_ssnit_employee_pesewas')->default(0);
            $table->unsignedBigInteger('total_paye_pesewas')->default(0);
            $table->unsignedBigInteger('total_other_deductions_pesewas')->default(0);
            $table->unsignedBigInteger('total_net_pesewas')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'period_month', 'period_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
