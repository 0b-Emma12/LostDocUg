<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ConsumeOcrResults implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct()
    {
        $this->onQueue('ocr-results');
    }

    public function handle(): void
    {
        try {
            $result = Redis::connection('ocr')->rpop('ocr:results');
        } catch (\Throwable $exception) {
            Log::warning('OCR result poll skipped: Redis unavailable', [
                'exception' => class_basename($exception),
            ]);

            return;
        }

        if (! $result) {
            self::dispatch()->delay(now()->addSeconds(5));

            return;
        }

        $data = json_decode($result, true);

        if (! is_array($data) || ! isset($data['extracted_field_id'])) {
            return;
        }

        if (! $this->validateSignature($data)) {
            report(new \RuntimeException('Invalid OCR result signature'));

            return;
        }

        HandleOcrResult::dispatch($data);
        self::dispatch();
    }

    private function validateSignature(array $data): bool
    {
        $signature = $data['signature'] ?? '';
        $payload = $data['payload'] ?? '';
        $expected = hash_hmac('sha256', $payload, config('lostdocug.ocr_shared_secret'));

        return hash_equals($expected, $signature);
    }
}
