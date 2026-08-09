<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // One row per student per virtual class, created the moment they click
    // Join (see StudentVirtualClassController::join()) - this app can't
    // see inside Zoom/Jitsi/Meet to know who's actually still present, so
    // "attendance" here means "clicked join at this time", not verified
    // in-call presence. That's an honest limitation worth the comment: a
    // student could click join and immediately leave the external tool,
    // and this table wouldn't know. Good enough for "did they show up
    // at all" tracking, not a substitute for the platform's own
    // attendance feature.
    public function up(): void
    {
        Schema::create('virtual_class_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_class_id')->constrained('virtual_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->timestamps();

            $table->unique(['virtual_class_id', 'student_id']); // one join record per student per session, even if they click Join more than once
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_class_attendance');
    }
};
