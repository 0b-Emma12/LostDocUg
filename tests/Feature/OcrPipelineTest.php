<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentOcr;
use App\Models\DocumentImage;
use App\Models\ExtractedField;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OcrPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_ocr_job_is_dispatched_to_redis(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $report = Report::factory()->for($user)->create();
        $image = DocumentImage::query()->create([
            'report_id' => $report->id,
            'storage_key' => 'reports/'.$report->id.'/demo.webp',
            'content_hash' => hash('sha256', 'demo-image-content'),
            'mime_type' => 'image/webp',
            'size_bytes' => 123,
            'width' => 120,
            'height' => 80,
            'scan_status' => 'pending',
            'retention_deadline' => now()->addDays(30),
        ]);

        ProcessDocumentOcr::dispatch($image);

        Queue::assertPushedOn('ocr', ProcessDocumentOcr::class);
        (new ProcessDocumentOcr($image))->handle();

        $this->assertDatabaseHas('extracted_fields', ['document_image_id' => $image->id, 'report_id' => $report->id]);
        $this->assertSame('processing', ExtractedField::query()->where('document_image_id', $image->id)->value('status'));
    }
}
