<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Platform-level reference data (Webb's DOK framework - not curriculum-
    // specific, not school-specific). This is deliberately NOT a
    // "class -> DOK" lookup table - we confirmed GES/NaCCA doesn't define
    // DOK that way; it varies per syllabus indicator based on both the verb
    // AND what comes after it. This table exists so the AI generation
    // prompt has a stable definition of each level and its representative
    // verbs to reason WITH when it reads an actual indicator - a reasoning
    // aid, not a classifier shortcut. See TeachingMaterialGenerationService.
    public function up(): void
    {
        Schema::create('dok_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level')->unique(); // 1-4
            $table->string('name', 50);
            $table->text('description');
            $table->json('representative_verbs');
            $table->timestamps();
        });

        DB::table('dok_levels')->insert([
            [
                'level' => 1,
                'name' => 'Recall & Reproduction',
                'description' => 'Basic tasks requiring the learner to recall or reproduce a fact, term, or simple procedure. Little to no transformation of the knowledge is required. Note: a Level 1 verb can still apply to a fairly complex fact - the verb alone does not fix difficulty.',
                'representative_verbs' => json_encode(['define', 'duplicate', 'label', 'list', 'memorise', 'name', 'order', 'recognise', 'relate', 'recall', 'reproduce', 'state', 'identify']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 2,
                'name' => 'Skills & Concepts',
                'description' => 'The learner must use information or conceptual knowledge, make some decisions about how to approach a problem, and engage with more than one step or mental process.',
                'representative_verbs' => json_encode(['classify', 'describe', 'discuss', 'explain', 'interpret', 'summarise', 'estimate', 'compare', 'organise']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 3,
                'name' => 'Strategic Thinking / Reasoning',
                'description' => 'Requires reasoning, planning, and evidence; the learner draws conclusions from observations, develops a logical argument, or applies concepts to a non-routine problem with justification.',
                'representative_verbs' => json_encode(['analyse', 'draw conclusions', 'justify', 'formulate', 'critique', 'investigate', 'construct', 'design']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'level' => 4,
                'name' => 'Extended Thinking',
                'description' => 'Complex reasoning, planning, and development over an extended period - designing and conducting an investigation, synthesising ideas across concepts into something new.',
                'representative_verbs' => json_encode(['design and conduct', 'synthesise', 'connect', 'create', 'develop a plan']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dok_levels');
    }
};
