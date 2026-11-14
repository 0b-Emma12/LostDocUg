<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportDraft extends Model
{
    protected $fillable = [
        'user_id',
        'draft_key',
        'payload',
        'last_touched_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'last_touched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
