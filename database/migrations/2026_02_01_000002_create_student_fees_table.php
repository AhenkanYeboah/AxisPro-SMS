<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A charge assigned to one specific student. Split from fee_payments
        // (the money received against it) rather than one running-balance
        // table - this supports partial payments cleanly and lets status be
        // derived rather than manually kept in sync in two places.
        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_item_id')->constrained()->cascadeOnDelete();

            // Snapshot of the amount at assignment time, in case fee_items
            // is edited later (e.g. a price change shouldn't retroactively
            // alter a bill that was already sent out).
            $table->unsignedBigInteger('amount_pesewas');

            $table->date('due_date')->nullable();
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'waived'])->default('unpaid');
            $table->timestamps();

            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fees');
    }
};
