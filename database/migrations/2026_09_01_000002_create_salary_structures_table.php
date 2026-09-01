<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Basic salary is versioned rather than a single column on `staff`
        // so a raise doesn't erase what someone was paid historically -
        // past payroll runs keep referencing the structure that was
        // current when they ran. The "current" one is whichever row has
        // the latest effective_from with no effective_to.
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedBigInteger('basic_salary_pesewas'); // GHS smallest unit, same convention as FeeItem/Payment
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'staff_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
