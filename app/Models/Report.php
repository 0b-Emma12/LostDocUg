<?php

namespace App\Models;

use App\Enums\DocumentKind;
use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use App\Enums\StationDropoffStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'kind',
        'document_type',
        'incident_date',
        'district',
        'location_description',
        'station_name',
        'station_address',
        'holder_name',
        'date_of_birth',
        'document_metadata',
        'consent_version',
        'expires_at',
    ];

    protected $hidden = [
        'holder_name',
        'date_of_birth',
        'document_metadata',
        'station_name',
        'station_address',
        'station_dropoff_status',
        'station_received_at',
        'station_received_by',
        'station_collected_at',
        'station_collected_by',
    ];

    protected $attributes = [
        'status' => 'processing',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ReportKind::class,
            'document_type' => DocumentKind::class,
            'status' => ReportStatus::class,
            'station_dropoff_status' => StationDropoffStatus::class,
            'incident_date' => 'date',
            'station_received_at' => 'datetime',
            'station_collected_at' => 'datetime',
            'holder_name' => 'encrypted',
            'date_of_birth' => 'encrypted',
            'document_metadata' => 'encrypted:array',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Report $report): void {
            if (blank($report->public_reference)) {
                $report->public_reference = 'LDG-'.Str::upper(Str::random(10));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(DocumentImage::class);
    }

    public function extractedFields(): HasMany
    {
        return $this->hasMany(ExtractedField::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    /** @param Builder<Report> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ReportStatus::Active,
            ReportStatus::Matched,
            ReportStatus::UnderReview,
        ]);
    }

    /** @param Builder<Report> $query */
    public function scopeOfType(Builder $query, DocumentKind $type): Builder
    {
        return $query->where('document_type', $type);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [ReportStatus::Processing, ReportStatus::Active], true);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->document_type?->value) {
            'nira_id' => 'National ID (NIRA)',
            'passport' => 'Ugandan Passport',
            'driving_licence' => 'Driving Licence',
            'land_title' => 'Land Title Deed',
            'academic_certificate' => 'Academic Certificate',
            'student_id' => 'Student ID',
            'bank_card' => 'Bank / ATM Card',
            'voter_document' => 'Voter Document',
            default => ucwords(str_replace('_', ' ', $this->document_type?->value ?? 'Document')),
        };
    }
}
