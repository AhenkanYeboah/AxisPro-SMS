<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A record of money actually received against a student_fee. Phase 1
        // is manual recording only (cash/mobile_money/bank) - 'paystack' is
        // in the enum now so Phase 2 online payments don't need a schema
        // migration later, just a new write path.
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_fee_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_pesewas');
            $table->enum('method', ['cash', 'mobile_money', 'bank', 'paystack'])->default('cash');
            $table->string('reference', 100)->nullable(); // momo/bank ref, or Paystack reference in Phase 2
            $table->foreignId('recorded_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();

            $table->index(['school_id', 'student_fee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
