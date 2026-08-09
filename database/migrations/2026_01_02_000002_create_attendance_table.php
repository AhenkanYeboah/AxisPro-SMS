<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'holiday'])->default('present');
            $table->unsignedTinyInteger('term')->default(1); // 1, 2, or 3

            // Original schema evolved at runtime (self-migrating ALTER TABLE calls)
            // to a 3-column unique key: student_id + date + term. This means a
            // student CAN have separate attendance rows for the same calendar
            // date across different terms - the upsert in AttendanceController
            // keys off all three columns to match.
            $table->unique(['student_id', 'date', 'term']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
