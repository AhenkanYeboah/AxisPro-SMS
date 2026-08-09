<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->cascadeOnDelete();
        });

        DB::table('teachers')->whereNull('school_id')->update(['school_id' => 1]);

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique('teachers_teacher_id_unique');
            $table->dropUnique('teachers_username_unique');
            $table->dropUnique('teachers_email_unique');
            $table->unique(['school_id', 'teacher_id']);
            $table->unique(['school_id', 'username']);
            $table->unique(['school_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'teacher_id']);
            $table->dropUnique(['school_id', 'username']);
            $table->dropUnique(['school_id', 'email']);
            $table->string('teacher_id', 20)->unique()->change();
            $table->string('username', 50)->unique()->change();
            $table->string('email', 100)->unique()->change();
            $table->dropConstrainedForeignId('school_id');
        });
    }
};
