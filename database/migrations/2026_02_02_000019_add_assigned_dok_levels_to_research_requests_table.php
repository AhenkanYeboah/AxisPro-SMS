<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Replaces the single assigned_dok_level_id with a set. Confirmed
    // design decision: a single class's material on one topic can
    // genuinely span several DOK levels at once (e.g. Class 6 English -
    // comprehension work sitting at DOK 4 while grammar/vocabulary in the
    // same lesson sits at DOK 2-3), because that's how NaCCA's own
    // indicators are written - not because a teacher chose a level. A
    // single FK can't represent that; this JSON array of DOK level
    // integers (e.g. [2,3,4]) can. assigned_dok_level_id is left in place
    // (unused going forward, not dropped) rather than migrated - it's
    // nullable and harmless to leave, and dropping it would be a
    // needless destructive step for a column no code will write to again.
    public function up(): void
    {
        Schema::table('research_requests', function (Blueprint $table) {
            $table->json('assigned_dok_levels')->nullable()->after('assigned_dok_level_id');
        });
    }

    public function down(): void
    {
        Schema::table('research_requests', function (Blueprint $table) {
            $table->dropColumn('assigned_dok_levels');
        });
    }
};
