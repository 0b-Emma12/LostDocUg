<?php

namespace App\Models;

use App\Enums\ImageScanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentImage extends Model
{
    protected $fillable = [
        'report_id',
        'storage_key',
        'content_hash',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'scan_status',
        'retention_deadline',
    ];

    protected function casts(): array
    {
        return [
            'scan_status' => ImageScanStatus::class,
            'retention_deadline' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function extractedField(): HasOne
    {
        return $this->hasOne(ExtractedField::class);
    }
}
