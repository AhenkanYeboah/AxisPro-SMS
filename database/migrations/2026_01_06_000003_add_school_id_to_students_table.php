<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->cascadeOnDelete();
        });

        DB::table('students')->whereNull('school_id')->update(['school_id' => 1]);

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_student_id_unique');
            $table->unique(['school_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'student_id']);
            $table->string('student_id', 20)->nullable()->unique()->change();
            $table->dropConstrainedForeignId('school_id');
        });
    }
};
