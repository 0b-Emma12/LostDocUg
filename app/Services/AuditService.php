<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function log(string $event, ?Report $report, array $context = []): void
    {
        Log::info('audit.event', [
            'event' => $event,
            'report_id' => $report?->id,
            'context' => $context,
        ]);
    }
}
