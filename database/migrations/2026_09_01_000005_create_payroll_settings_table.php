<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('ssnit_employee_rate', 5, 2)->default(5.5); // % of basic salary, employee side
            $table->decimal('ssnit_employer_rate', 5, 2)->default(13.5); // % of basic salary, employer side (informational - not deducted from staff)
            $table->unsignedBigInteger('ssnit_ceiling_pesewas')->nullable(); // monthly insurable earnings ceiling; null = uncapped. VERIFY against the current SSNIT notice before relying on this.
            $table->unsignedTinyInteger('pay_day_of_month')->nullable();
            $table->string('currency', 3)->default('GHS');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
