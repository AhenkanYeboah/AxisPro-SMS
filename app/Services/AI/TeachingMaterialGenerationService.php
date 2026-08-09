<?php

namespace App\Services\AI;

use App\Models\ClassLevel;
use App\Models\DokLevel;
use App\Models\ResearchRequest;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Support\Facades\Log;

/**
 * The core of the research assistant: takes a teacher's request, retrieves
 * grounding material (SyllabusRetrievalService), builds a prompt that
 * constrains the AI to that material, calls Claude (ClaudeClient), and
 * persists everything - including which chunks were used - as a
 * research_requests row. That row is the audit trail: "not hallucinating"
 * is a checkable claim only because a teacher (or admin) can trace the
 * output back to the exact syllabus indicators it was grounded in.
 *
 * DOK level is never chosen by the teacher (no override parameter exists
 * here) - confirmed design decision: GES/NaCCA doesn't publish a per-class
 * DOK ceiling, so a manual picker would be enforcing a rule nobody
 * actually stated. Instead the AI derives DOK from the real indicator
 * text it retrieved, and - since one topic can legitimately span several
 * indicators at different levels within the same class (e.g. Class 6
 * English comprehension sitting at DOK 4 while grammar in the same lesson
 * sits at DOK 2-3) - the result is a SET of levels, not one.
 */
class TeachingMaterialGenerationService
{
    public function __construct(
        private SyllabusRetrievalService $retrieval,
        private ClaudeClient $claude,
    ) {
    }

    public function generate(
        Teacher $teacher,
        ClassLevel $classLevel,
        Subject $subject,
        string $topic,
        string $materialType,
    ): ResearchRequest {
        $request = ResearchRequest::create([
            'school_id' => $teacher->school_id,
            'teacher_id' => $teacher->id,
            'class_level_id' => $classLevel->id,
            'subject_id' => $subject->id,
            'topic' => $topic,
            'material_type' => $materialType,
            'status' => 'pending',
        ]);

        try {
            $chunks = $this->retrieval->findChunks($classLevel, $subject->id, $topic);
            $exemplars = $this->retrieval->findExemplars($classLevel, $subject->id, $materialType);

            if ($chunks->isEmpty()) {
                // No syllabus content matched. This is the "not
                // hallucinating" guarantee in practice: refuse to generate
                // ungrounded material rather than let the model fall back
                // on general training knowledge about the topic. The
                // teacher gets a clear, actionable failure instead of a
                // plausible-looking but unverifiable lesson.
                $request->update([
                    'status' => 'failed',
                    'error_message' => "No matching syllabus content found for \"{$topic}\" under {$classLevel->curriculum?->name} / {$subject->name} / {$classLevel->displayName()}. "
                        ."Either the syllabus document for this subject hasn't been uploaded yet, or the topic doesn't match the curriculum's wording - try the exact strand/sub-strand name.",
                ]);

                return $request;
            }

            $systemPrompt = $this->buildSystemPrompt($classLevel, $subject, $chunks, $exemplars);
            $userMessage = $this->buildUserMessage($topic, $materialType);

            $rawOutput = $this->claude->generate($systemPrompt, $userMessage);
            [$cleanedContent, $dokLevels] = $this->extractDokLevels($rawOutput);

            $request->update([
                'generated_content' => $cleanedContent,
                'source_chunk_ids' => $chunks->pluck('id')->values()->all(),
                'assigned_dok_levels' => $dokLevels,
                'status' => 'completed',
            ]);

            return $request;
        } catch (\Throwable $e) {
            Log::error('TeachingMaterialGenerationService: generation failed.', [
                'research_request_id' => $request->id,
                'message' => $e->getMessage(),
            ]);

            $request->update([
                'status' => 'failed',
                'error_message' => 'Something went wrong generating this material. Please try again.',
            ]);

            return $request;
        }
    }

    // The prompt (see buildSystemPrompt) requires the model to end its
    // response with a machine-readable marker line, e.g.
    // "[DOK_LEVELS: 2,3,4]" - listing every distinct level actually used
    // across the material, not just one. This pulls that line out (so the
    // teacher never sees the raw marker) and turns it into a clean sorted
    // array for assigned_dok_levels. Best-effort: if the model didn't
    // include a well-formed marker, the levels are simply left empty
    // rather than failing the whole generation - the DOK reasoning is
    // still visible to the teacher inline in the material itself either
    // way, this only affects the structured badge/filter data.
    private function extractDokLevels(string $rawOutput): array
    {
        if (! preg_match('/\[DOK_LEVELS:\s*([0-9,\s]+)\]\s*$/i', trim($rawOutput), $matches)) {
            return [$rawOutput, []];
        }

        $levels = collect(explode(',', $matches[1]))
            ->map(fn ($n) => (int) trim($n))
            ->filter(fn ($n) => $n >= 1 && $n <= 4)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $cleaned = trim(preg_replace('/\[DOK_LEVELS:\s*[0-9,\s]+\]\s*$/i', '', trim($rawOutput)));

        return [$cleaned, $levels];
    }

    private function buildSystemPrompt(
        ClassLevel $classLevel,
        Subject $subject,
        \Illuminate\Support\Collection $chunks,
        \Illuminate\Support\Collection $exemplars,
    ): string {
        $curriculumName = $classLevel->curriculum?->name ?? 'the specified curriculum';

        $syllabusContext = $chunks->map(function ($chunk, $i) {
            $ref = $chunk->indicator_code ? " [{$chunk->indicator_code}]" : '';

            return "--- Source ".($i + 1)."{$ref} ({$chunk->strand} / {$chunk->sub_strand}) ---\n{$chunk->content}";
        })->implode("\n\n");

        $dokLevels = DokLevel::orderBy('level')->get()
            ->map(fn ($level) => $level->toPromptBlock())
            ->implode("\n");

        $exemplarBlock = $exemplars->isEmpty()
            ? ''
            : "\n\nAPPROVED STYLE REFERENCES (match this tone/format, do not copy their subject matter):\n"
                .$exemplars->map(fn ($e) => "--- {$e->title} ---\n{$e->content}")->implode("\n\n");

        return <<<PROMPT
You are a curriculum-grounded teaching assistant for Ghanaian and international school teachers, built into the AxisPro School Management System.

You are generating material for: {$curriculumName}, subject "{$subject->name}", class {$classLevel->displayName()}.

CRITICAL GROUNDING RULE: You must base the content ONLY on the syllabus sources provided below. Do not introduce facts, examples, or scope beyond what these sources support. If the sources are insufficient to fully answer the request, say so explicitly rather than filling the gap from general knowledge - an incomplete but accurate answer is required over a complete but unverifiable one.

SYLLABUS SOURCES:
{$syllabusContext}

DEPTH OF KNOWLEDGE (DOK) REFERENCE:
{$dokLevels}

DOK HANDLING - READ CAREFULLY: No DOK level is requested by the teacher, and none should be assumed from the class alone - GES/NaCCA does not define a fixed DOK ceiling per class. Instead, judge the DOK level directly from each syllabus source's own indicator text and action verb(s) above (remember: the verb alone is not sufficient, consider what the indicator actually asks the learner to do).

Crucially, a single topic can legitimately touch multiple sources at DIFFERENT DOK levels within the same class - for example, comprehension work might call for DOK 4 reasoning while a grammar/vocabulary component of the same topic calls for DOK 2-3. Do not flatten this into one level. Where your sources support it, produce material that spans the full range the indicators actually call for, and label which part of the material corresponds to which DOK level and source, so a teacher can see the reasoning, not just the output.
{$exemplarBlock}

Cite which source(s) above (by number/indicator code) each part of your material draws from, inline or in a closing note.

REQUIRED LAST LINE: end your entire response with a single line listing every distinct DOK level actually used across the material, in this exact machine-readable format and nothing else on that line: [DOK_LEVELS: 2,3,4] (use the real levels you used, comma-separated, no spaces required). This line will be parsed automatically and removed before the teacher sees the material - it must be the last thing in your response.
PROMPT;
    }

    private function buildUserMessage(string $topic, string $materialType): string
    {
        return "Generate a {$materialType} on the topic: \"{$topic}\".";
    }
}
