<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // GRA PAYE bands change periodically, so they live in a table
        // (seeded with the 2026 bands) rather than hardcoded in the
        // calculator. school_id is nullable: null rows are the platform
        // default set every tenant falls back to; a school can get its
        // own override rows for a given effective_year if it ever needs
        // a different schedule.
        Schema::create('payroll_tax_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('effective_year');
            $table->unsignedTinyInteger('band_order'); // 1 = lowest band
            $table->unsignedBigInteger('annual_lower_bound_pesewas');
            $table->unsignedBigInteger('annual_upper_bound_pesewas')->nullable(); // null = top band, no ceiling
            $table->decimal('rate_percentage', 5, 2); // e.g. 17.50
            $table->timestamps();

            $table->index(['school_id', 'effective_year', 'band_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_tax_bands');
    }
};
