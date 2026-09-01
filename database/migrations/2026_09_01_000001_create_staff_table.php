<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // HR/payroll record for anyone the school pays - teaching staff,
        // admin staff, and non-teaching support staff (cleaners, security,
        // drivers, cooks, etc). Deliberately separate from the Teacher and
        // Admin auth models: not every staff member needs (or should have)
        // a login to the platform, and a teacher/admin who does log in can
        // still be linked here via teacher_id/admin_id so their payroll
        // record and their portal account are the same person.
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('staff_no', 30); // school-defined employee number, unique per school
            $table->string('full_name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('position', 100); // e.g. "Mathematics Teacher", "Accountant", "Security"
            $table->string('department', 100)->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'casual'])->default('full_time');
            $table->date('date_joined')->nullable();
            $table->date('date_left')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('mobile_money_provider', 50)->nullable();
            $table->string('mobile_money_number', 30)->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'staff_no']);
            $table->index(['school_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
