<?php

namespace App\Jobs;

use App\Models\CurriculumDocument;
use App\Services\AI\SyllabusIngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs a syllabus document through SyllabusIngestionService. Queued rather
 * than run inline on upload for the same reason SendNoticeJob is queued -
 * parsing a 40+ page PDF can take a while and shouldn't hold the platform
 * admin's upload request open. The document's ingestion_status column is
 * how the admin's document list reflects progress.
 */
class IngestCurriculumDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public CurriculumDocument $document)
    {
    }

    public function handle(SyllabusIngestionService $ingestion): void
    {
        $ingestion->ingest($this->document);
    }
}
