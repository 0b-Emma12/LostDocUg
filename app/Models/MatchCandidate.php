<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchCandidate extends Model
{
    protected $fillable = [
        'lost_report_id',
        'found_report_id',
        'score',
        'reason_codes',
        'field_agreements',
        'scoring_version',
        'status',
        'reviewer_id',
        'reviewer_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'reason_codes' => 'array',
            'field_agreements' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function lostReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'lost_report_id');
    }

    public function foundReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'found_report_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
