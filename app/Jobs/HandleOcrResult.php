<?php

namespace App\Jobs;

use App\Models\DocumentImage;
use App\Models\ExtractedField;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HandleOcrResult implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(public array $result)
    {
        $this->onQueue('ocr-results');
    }

    public function handle(AuditService $audit): void
    {
        $extractedField = ExtractedField::query()->find($this->result['extracted_field_id']);

        if (! $extractedField) {
            Log::warning('OCR result for unknown extracted_field', [
                'extracted_field_id' => $this->result['extracted_field_id'] ?? null,
            ]);

            return;
        }

        $image = DocumentImage::query()->find($this->result['image_id'] ?? null);

        if (($this->result['status'] ?? null) === 'success') {
            $extractedField->setFieldsPayload($this->result['fields'] ?? []);

            $extractedField->update([
                'status' => ($this->result['review_required'] ?? false) ? 'needs_review' : 'completed',
                'review_required' => (bool) ($this->result['review_required'] ?? false),
                'review_reason' => $this->result['review_reason'] ?? null,
                'overall_confidence' => $this->result['overall_confidence'] ?? null,
                'schema_version' => $this->result['schema_version'] ?? 1,
                'parser_version' => $this->result['parser_version'] ?? '1',
                'engine' => $this->result['engine'] ?? 'paddleocr',
                'error_message' => null,
            ]);

            if ($image) {
                $image->update(['scan_status' => 'completed']);
            }

            $audit->log('ocr.completed', $extractedField->report, [
                'document_type' => $extractedField->document_type,
                'confidence' => $this->result['overall_confidence'] ?? null,
                'review_required' => (bool) ($this->result['review_required'] ?? false),
            ]);

            if (! ($this->result['review_required'] ?? false)) {
                // Matching dispatch remains intentionally disabled in this prototype until a matching engine is implemented.
            }

            return;
        }

        if (($this->result['status'] ?? null) === 'failed') {
            $extractedField->update([
                'status' => 'failed',
                'error_message' => $this->result['error'] ?? 'Unknown OCR error',
                'review_required' => true,
                'review_reason' => 'ocr_failure',
            ]);

            if ($image) {
                $image->update(['scan_status' => 'failed']);
            }

            Log::warning('OCR processing failed', [
                'image_id' => $this->result['image_id'] ?? null,
                'extracted_field_id' => $this->result['extracted_field_id'] ?? null,
                'error_class' => class_basename($this->result['error'] ?? 'Unknown'),
            ]);
        }
    }
}
