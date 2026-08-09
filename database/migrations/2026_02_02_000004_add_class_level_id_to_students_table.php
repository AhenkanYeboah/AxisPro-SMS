<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The `class` string column is left in place deliberately - it's not
    // dropped here. The backfill migration (...000009) populates
    // class_level_id from it but doesn't touch/remove the original column,
    // so this stays a safe, reversible step. A later cleanup migration can
    // drop `class` once every read path in the app has been moved over to
    // class_level_id (out of scope for this change).
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('class_level_id')->nullable()->after('class')->constrained('class_levels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_level_id');
        });
    }
};
