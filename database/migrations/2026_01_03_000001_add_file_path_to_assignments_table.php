<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The submission's version of assignments.php lets a teacher optionally
    // attach a file (worksheet, instructions, etc.) when posting an
    // assignment. That needs a nullable file_path column here.
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
