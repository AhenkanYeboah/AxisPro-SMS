<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Stays nullable - null means "not class-scoped" (see the `class`
    // column's own comment: only meaningful when audience = 'class').
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->foreignId('class_level_id')->nullable()->after('class')->constrained('class_levels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_level_id');
        });
    }
};
