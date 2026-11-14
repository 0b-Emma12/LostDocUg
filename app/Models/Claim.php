<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Claim extends Model
{
    protected $fillable = [
        'match_candidate_id',
        'claimant_id',
        'report_id',
        'status',
        'encrypted_evidence',
        'claimant_statement',
        'reviewer_id',
        'decision_reason',
        'reviewer_notes',
        'reviewed_at',
        'recovered_at',
        'recovery_method',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'recovered_at' => 'datetime',
            'claimant_statement' => 'encrypted',
        ];
    }

    public function matchCandidate(): BelongsTo
    {
        return $this->belongsTo(MatchCandidate::class);
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimant_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function verificationAttempts(): HasMany
    {
        return $this->hasMany(VerificationAttempt::class);
    }

    public function setEvidence(array $evidence): void
    {
        $this->encrypted_evidence = Crypt::encryptString(json_encode($evidence, JSON_THROW_ON_ERROR));
    }

    public function getEvidence(): ?array
    {
        if (! $this->encrypted_evidence) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($this->encrypted_evidence), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
