<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // One row per scheduled video session, regardless of which video
    // platform actually hosts it - `platform` + `join_url` is the whole
    // abstraction. Three platform values, three different join_url
    // origins:
    //   'zoom_api'       - join_url comes back from ZoomService::createMeeting()
    //                      (real Zoom S2S OAuth API call), zoom_meeting_id
    //                      stored alongside for future cancel/update calls.
    //   'jitsi_auto'     - join_url generated locally with zero external
    //                      setup (https://meet.jit.si/{random-room-name}),
    //                      always available even with no API keys configured
    //                      at all - the guaranteed-to-work fallback.
    //   'external_link'  - teacher pastes a link they created themselves
    //                      (personal Zoom, Google Meet, Teams, anything) -
    //                      system just stores and displays it.
    public function up(): void
    {
        Schema::create('virtual_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();

            // Scoped to a class_level, not a freetext class string - a
            // virtual class is exactly the kind of thing that should have
            // existed on class_levels from the start (see that table's
            // migration), so no legacy string column here at all.
            $table->foreignId('class_level_id')->constrained('class_levels')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();

            $table->string('title', 200);
            $table->enum('platform', ['zoom_api', 'jitsi_auto', 'external_link'])->default('jitsi_auto');
            $table->string('join_url', 500);

            // Only meaningful for platform='zoom_api' - needed if a cancel/
            // reschedule ever calls Zoom's API again to update/delete the
            // meeting server-side, not just remove the local row.
            $table->string('zoom_meeting_id', 50)->nullable();

            $table->text('host_notes')->nullable(); // teacher-only prep notes, never shown to students

            $table->dateTime('scheduled_start');
            $table->dateTime('scheduled_end');

            // Deliberately explicit rather than purely derived from
            // scheduled_start/end - 'cancelled' can't be expressed by time
            // comparison alone, and an admin/teacher explicitly cancelling
            // should be a recorded action, not just an implied state.
            $table->enum('status', ['scheduled', 'ended', 'cancelled'])->default('scheduled');

            $table->timestamps();

            $table->index(['school_id', 'class_level_id', 'scheduled_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_classes');
    }
};
