<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('profile_image')->nullable();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('email', 150);
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('region', 100)->nullable(); // one of the 16 regions of Ghana
            $table->string('district', 100)->nullable();
            $table->string('next_of_kin', 150)->nullable();
            $table->string('class', 50)->nullable();
            $table->enum('admission_status', ['admitted', 'undecided'])->default('undecided');
            $table->timestamp('created_at')->useCurrent();
            $table->string('student_id', 20)->nullable()->unique();
            $table->string('password')->nullable();
            $table->date('exam_date')->nullable();
            $table->boolean('exam_completed')->default(false);
            $table->boolean('exam_verified')->default(false);
            $table->enum('status', ['pending', 'active', 'declined'])->default('pending');
            $table->string('results_file')->nullable();

            $table->index('admission_status');
            $table->index('gender');
            $table->index('class');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
