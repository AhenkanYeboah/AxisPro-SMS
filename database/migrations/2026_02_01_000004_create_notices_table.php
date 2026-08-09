<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A message an admin composes and sends to parents - used both for
        // general notices and for fee reminders. Delivery itself is tracked
        // per-recipient in notice_recipients (next migration), since a
        // "sent" notice can partially fail across a class of 30+ students.
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('title', 200);
            $table->text('body');
            $table->enum('audience', ['all', 'class', 'individual'])->default('all');
            $table->string('class', 50)->nullable(); // only meaningful when audience = 'class'
            $table->enum('channel', ['email', 'sms', 'both'])->default('both');
            $table->enum('status', ['draft', 'sending', 'sent', 'failed'])->default('draft');
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
