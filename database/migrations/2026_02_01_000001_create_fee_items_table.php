<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A chargeable "thing" a school defines - termly fees, lunch, a
        // trip levy, etc. Not itself a bill for any one student; assigning
        // it to students creates rows in student_fees (see next migration).
        Schema::create('fee_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount_pesewas'); // GHS smallest unit, same convention as Payment
            $table->string('class', 50)->nullable(); // null = applies to all classes
            $table->enum('frequency', ['one_off', 'termly', 'monthly'])->default('termly');
            $table->string('term', 20)->nullable(); // e.g. "Term 1" - only meaningful when frequency is termly
            $table->string('academic_year', 20)->nullable(); // e.g. "2026/2027"
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'is_active']);
            $table->index(['school_id', 'class']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_items');
    }
};
