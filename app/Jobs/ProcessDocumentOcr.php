<?php

namespace App\Jobs;

use App\Models\DocumentImage;
use App\Models\ExtractedField;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ProcessDocumentOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $timeout = 120;

    public function __construct(public DocumentImage $image)
    {
        $this->onQueue('ocr');
    }

    public function handle(): void
    {
        $extractedField = ExtractedField::query()->updateOrCreate(
            ['document_image_id' => $this->image->id],
            [
                'report_id' => $this->image->report_id,
                'document_type' => $this->image->report->document_type->value,
                'encrypted_payload' => '',
                'status' => 'processing',
                'engine' => 'paddleocr',
                'parser_version' => '1',
                'schema_version' => 1,
            ],
        );

        $this->image->update(['scan_status' => 'processing']);

        $jobId = (string) Str::uuid();

        try {
            Redis::connection('ocr')->lpush('ocr:requests', json_encode([
                'job_id' => $jobId,
                'image_id' => $this->image->id,
                'report_id' => $this->image->report_id,
                'extracted_field_id' => $extractedField->id,
                'document_type' => $this->image->report->document_type->value,
                'storage_key' => $this->image->storage_key,
                'timestamp' => now()->toIso8601String(),
            ]));
        } catch (\Throwable $exception) {
            Log::warning('OCR dispatch skipped: Redis unavailable', [
                'image_id' => $this->image->id,
                'exception' => class_basename($exception),
            ]);

            return;
        }

        $extractedField->update(['error_message' => $jobId]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->image->update(['scan_status' => 'failed']);

        ExtractedField::query()->where('document_image_id', $this->image->id)->update([
            'status' => 'failed',
            'error_message' => 'OCR dispatch failed: '.class_basename($exception),
            'review_required' => true,
            'review_reason' => 'dispatch_failure',
        ]);
    }
}
