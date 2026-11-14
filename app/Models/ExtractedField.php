<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ExtractedField extends Model
{
    protected $fillable = [
        'document_image_id',
        'report_id',
        'encrypted_payload',
        'key_version',
        'schema_version',
        'document_type',
        'engine',
        'parser_version',
        'review_required',
        'review_reason',
        'overall_confidence',
        'status',
        'error_message',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'review_required' => 'boolean',
            'overall_confidence' => 'decimal:4',
            'schema_version' => 'integer',
            'key_version' => 'integer',
            'attempts' => 'integer',
        ];
    }

    public function documentImage(): BelongsTo
    {
        return $this->belongsTo(DocumentImage::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function setFieldsPayload(array $fields): void
    {
        $this->encrypted_payload = Crypt::encryptString(json_encode($fields, JSON_THROW_ON_ERROR));
        $this->key_version = config('lostdocug.encryption_key_version', 1);
    }

    public function getFieldsPayload(): ?array
    {
        try {
            $decrypted = Crypt::decryptString($this->encrypted_payload);

            return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function getField(string $fieldName): ?array
    {
        $fields = $this->getFieldsPayload();

        return $fields['fields'][$fieldName] ?? null;
    }

    public function isReliableForMatching(): bool
    {
        return $this->status === 'completed'
            && ! $this->review_required
            && (float) $this->overall_confidence >= 0.7;
    }
}
