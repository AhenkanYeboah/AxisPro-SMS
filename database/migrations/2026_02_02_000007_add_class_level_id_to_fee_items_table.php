<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Stays nullable for the same reason `class` is nullable here: null
    // means "applies to all classes", not "unknown class".
    public function up(): void
    {
        Schema::table('fee_items', function (Blueprint $table) {
            $table->foreignId('class_level_id')->nullable()->after('class')->constrained('class_levels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fee_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_level_id');
        });
    }
};
