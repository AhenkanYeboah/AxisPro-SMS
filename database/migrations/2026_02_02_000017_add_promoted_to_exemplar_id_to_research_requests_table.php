<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Marks a research_request as already promoted into curriculum_exemplars,
    // so the platform admin's "candidates" list (helpful requests not yet
    // promoted) doesn't keep showing the same one forever, and so a
    // request's own view can show "this became an exemplar" instead of a
    // dangling "mark helpful" prompt.
    public function up(): void
    {
        Schema::table('research_requests', function (Blueprint $table) {
            $table->foreignId('promoted_to_exemplar_id')->nullable()->after('marked_helpful')
                ->constrained('curriculum_exemplars')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('research_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promoted_to_exemplar_id');
        });
    }
};
