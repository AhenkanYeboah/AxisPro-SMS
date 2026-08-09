<?php

namespace App\Services\AI;

use App\Models\CurriculumDocument;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Turns an uploaded syllabus PDF into curriculum_document_chunks rows.
 * Heuristic, not a formal parser - NaCCA (and presumably Cambridge)
 * syllabus documents aren't machine-readable structured data, they're
 * PDFs meant for a human reader. This extracts what structure it can
 * (STRAND/SUB-STRAND headers, indicator codes, class markers) from the
 * text layout and falls back to fixed-size chunking where it can't.
 *
 * This is explicitly a best-effort first pass: after ingestion, a
 * platform admin should spot-check a sample of chunks (curriculum
 * document review screen - not yet built) before trusting retrieval
 * against them at scale. Getting 90% of a 40-page syllabus chunked
 * usefully and flagging the rest for review beats blocking on a perfect
 * parser.
 */
class SyllabusIngestionService
{
    private const TARGET_CHUNK_CHARS = 1200; // rough size for a fallback chunk when no structure is detected

    public function ingest(CurriculumDocument $document): void
    {
        $document->update(['ingestion_status' => 'processing', 'ingestion_error' => null]);

        try {
            $absolutePath = storage_path('app/public/'.$document->file_path);

            $parser = new PdfParser();
            $pdf = $parser->parseFile($absolutePath);

            $document->chunks()->delete(); // re-ingesting replaces prior chunks rather than duplicating

            $chunkIndex = 0;

            foreach ($pdf->getPages() as $pageNumber => $page) {
                $text = $page->getText();
                $chunks = $this->splitPageIntoChunks($text);

                foreach ($chunks as $chunk) {
                    $document->chunks()->create([
                        'curriculum_id' => $document->curriculum_id,
                        'subject_id' => $document->subject_id,
                        'class_tag' => $chunk['class_tag'],
                        'strand' => $chunk['strand'],
                        'sub_strand' => $chunk['sub_strand'],
                        'indicator_code' => $chunk['indicator_code'],
                        'chunk_index' => $chunkIndex++,
                        'page_number' => $pageNumber + 1,
                        'content' => $chunk['content'],
                    ]);
                }
            }

            // A scanned/image-only PDF (no embedded text layer) parses
            // "successfully" but getText() returns empty on every page,
            // producing zero chunks - without this check that would
            // silently mark as 'completed' and the document would just
            // never surface in retrieval, with no signal to the admin
            // about why. OCR is out of scope for this phase (see class
            // docblock) - the actionable fix right now is re-exporting or
            // re-scanning the source as a text-layer PDF, which is what
            // the error message should point the admin toward.
            if ($chunkIndex === 0) {
                throw new \RuntimeException(
                    'No extractable text found in this PDF - it may be a scanned/image-only document. '
                    .'Re-export it as a text-layer PDF (not a scanned image) and re-upload.'
                );
            }

            $document->update(['ingestion_status' => 'completed']);
        } catch (\Throwable $e) {
            $document->update([
                'ingestion_status' => 'failed',
                'ingestion_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<int, array{class_tag: ?string, strand: ?string, sub_strand: ?string, indicator_code: ?string, content: string}>
     */
    private function splitPageIntoChunks(string $pageText): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $pageText);

        $currentClassTag = null;
        $currentStrand = null;
        $currentSubStrand = null;
        $currentIndicatorCode = null;
        $buffer = [];
        $chunks = [];

        $flush = function () use (&$buffer, &$chunks, &$currentClassTag, &$currentStrand, &$currentSubStrand, &$currentIndicatorCode) {
            $content = trim(implode("\n", $buffer));

            if ($content !== '') {
                $chunks[] = [
                    'class_tag' => $currentClassTag,
                    'strand' => $currentStrand,
                    'sub_strand' => $currentSubStrand,
                    'indicator_code' => $currentIndicatorCode,
                    'content' => $content,
                ];
            }

            $buffer = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            // Class marker, e.g. "B7" / "BASIC 7" / "JHS 1" appearing as a
            // standalone header line - update running context but don't
            // start a new chunk on its own (it usually precedes a strand
            // header on the next line).
            if (preg_match('/^(?:BASIC|B|JHS|SHS|YEAR)\s*0*(\d{1,2})\s*(?:\/\s*(?:JHS|SHS)\s*0*(\d{1,2}))?$/i', $trimmed)) {
                $currentClassTag = $trimmed;

                continue;
            }

            // "STRAND: ..." or "SUB-STRAND: ..." headers start a new chunk
            // - this is the primary structural signal NaCCA documents use.
            if (preg_match('/^STRAND\s*[:\-]\s*(.+)/i', $trimmed, $m)) {
                $flush();
                $currentStrand = trim($m[1]);
                $currentSubStrand = null;
                $currentIndicatorCode = null;

                continue;
            }

            if (preg_match('/^SUB[\s-]?STRAND\s*[:\-]\s*(.+)/i', $trimmed, $m)) {
                $flush();
                $currentSubStrand = trim($m[1]);
                $currentIndicatorCode = null;

                continue;
            }

            // Indicator codes, e.g. "B7.1.1.1.1" or "B7/JHS1.1.1.1.2" -
            // starts a new chunk since each indicator (plus its exemplars)
            // is the unit we want retrievable on its own.
            if (preg_match('/^((?:B|JHS|SHS)\d{1,2}(?:\/(?:JHS|SHS)\d{1,2})?\.\d+(?:\.\d+)*)\b/i', $trimmed, $m)) {
                $flush();
                $currentIndicatorCode = $m[1];
                $buffer[] = $trimmed;

                continue;
            }

            $buffer[] = $trimmed;

            // Fallback size cap: if no structural marker has appeared for
            // a while (e.g. a dense narrative page with no indicator
            // codes), flush anyway so a single chunk doesn't grow
            // unboundedly and dilute retrieval relevance.
            if (mb_strlen(implode("\n", $buffer)) > self::TARGET_CHUNK_CHARS) {
                $flush();
            }
        }

        $flush();

        return $chunks;
    }
}
