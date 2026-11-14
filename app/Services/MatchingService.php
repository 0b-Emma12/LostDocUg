<?php

namespace App\Services;

use App\Enums\DocumentKind;
use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use App\Models\MatchCandidate;
use App\Models\Report;

class MatchingService
{
    private const RELAXED_MATCH_DOCUMENT_TYPES = [
        DocumentKind::LandTitle,
        DocumentKind::AcademicCertificate,
        DocumentKind::StudentId,
        DocumentKind::VoterDocument,
    ];

    public function __construct(private readonly NormalizationService $normalizer) {}

    public function findMatches(Report $report): int
    {
        if (blank($report->holder_name) || $report->document_type === DocumentKind::BankCard) {
            return 0;
        }

        $oppositeKind = $report->kind === ReportKind::Lost ? ReportKind::Found : ReportKind::Lost;
        $normalizedName = $this->normalizer->normalize($report->holder_name, 'exact');

        if ($normalizedName === '') {
            return 0;
        }

        $createdMatches = 0;
        $allowsRelaxedNameMatch = in_array($report->document_type, self::RELAXED_MATCH_DOCUMENT_TYPES, true);

        Report::query()
            ->where('kind', $oppositeKind)
            ->where('document_type', $report->document_type)
            ->whereIn('status', [ReportStatus::Processing, ReportStatus::Active])
            ->whereNotNull('holder_name')
            ->whereKeyNot($report->id)
            ->orderBy('id')
            ->chunkById(100, function ($candidateReports) use ($report, $allowsRelaxedNameMatch, &$createdMatches): void {
                foreach ($candidateReports as $candidateReport) {
                    $nameMatchType = $this->matchName(
                        $report->holder_name,
                        $candidateReport->holder_name,
                        $allowsRelaxedNameMatch,
                    );

                    if ($nameMatchType === null) {
                        continue;
                    }

                    $lostReport = $report->kind === ReportKind::Lost ? $report : $candidateReport;
                    $foundReport = $report->kind === ReportKind::Found ? $report : $candidateReport;
                    $lostDateOfBirth = $lostReport->date_of_birth;
                    $foundDateOfBirth = $foundReport->date_of_birth;

                    if (filled($lostDateOfBirth) && filled($foundDateOfBirth)) {
                        $normalizedLostDate = $this->normalizer->normalize($lostDateOfBirth, 'exact');
                        $normalizedFoundDate = $this->normalizer->normalize($foundDateOfBirth, 'exact');

                        if (! hash_equals($normalizedLostDate, $normalizedFoundDate)) {
                            continue;
                        }
                    }

                    $dateOfBirthMatches = filled($lostDateOfBirth) && filled($foundDateOfBirth);
                    $nameReason = 'holder_name_'.$nameMatchType;
                    $academicMetadata = $this->academicMetadataAgreements($lostReport, $foundReport);

                    $match = MatchCandidate::query()->firstOrCreate(
                        [
                            'lost_report_id' => $lostReport->id,
                            'found_report_id' => $foundReport->id,
                        ],
                        [
                            'score' => match (true) {
                                $nameMatchType !== 'exact' => 40,
                                $dateOfBirthMatches => 85,
                                default => 60,
                            },
                            'reason_codes' => [
                                $nameReason,
                                ...($dateOfBirthMatches ? ['date_of_birth_match'] : []),
                                ...$academicMetadata['reason_codes'],
                                'requires_manual_review',
                            ],
                            'field_agreements' => [
                                'holder_name' => ['match' => true, 'match_type' => $nameMatchType],
                                ...($dateOfBirthMatches ? ['date_of_birth' => ['match' => true]] : []),
                                ...$academicMetadata['field_agreements'],
                            ],
                            'scoring_version' => '2.2',
                            'status' => 'pending',
                        ],
                    );

                    if ($match->wasRecentlyCreated) {
                        $createdMatches++;
                    }
                }
            });

        return $createdMatches;
    }

    private function matchName(string $name, string $candidateName, bool $allowsRelaxedMatch): ?string
    {
        $normalizedName = $this->normalizer->normalize($name, 'exact');
        $normalizedCandidate = $this->normalizer->normalize($candidateName, 'exact');

        if ($normalizedCandidate === '') {
            return null;
        }

        if (hash_equals($normalizedName, $normalizedCandidate)) {
            return 'exact';
        }

        if (! $allowsRelaxedMatch) {
            return null;
        }

        $fuzzyName = $this->normalizer->normalize($name, 'fuzzy');
        $fuzzyCandidate = $this->normalizer->normalize($candidateName, 'fuzzy');

        if ($fuzzyName === '' || $fuzzyCandidate === '') {
            return null;
        }

        similar_text($fuzzyName, $fuzzyCandidate, $similarity);

        if ($similarity >= 85) {
            return 'fuzzy';
        }

        $nameParts = array_unique(explode(' ', $fuzzyName));
        $candidateParts = array_unique(explode(' ', $fuzzyCandidate));
        $sharedParts = array_intersect($nameParts, $candidateParts);

        if (count($sharedParts) >= 2) {
            return 'partial';
        }

        $sharedPart = reset($sharedParts);

        if (count($sharedParts) === 1
            && (count($nameParts) === 1 || count($candidateParts) === 1)
            && $sharedPart !== false
            && mb_strlen($sharedPart, 'UTF-8') >= 6) {
            return 'partial';
        }

        return null;
    }

    /**
     * @return array{
     *     reason_codes: list<string>,
     *     field_agreements: array<string, array{match: bool}>
     * }
     */
    private function academicMetadataAgreements(Report $lostReport, Report $foundReport): array
    {
        if (! in_array($lostReport->document_type, [
            DocumentKind::AcademicCertificate,
            DocumentKind::StudentId,
        ], true)) {
            return ['reason_codes' => [], 'field_agreements' => []];
        }

        $lostMetadata = $lostReport->document_metadata ?? [];
        $foundMetadata = $foundReport->document_metadata ?? [];
        $reasonCodes = [];
        $fieldAgreements = [];

        foreach (['institution_name', 'certificate_type', 'completion_year', 'academic_year'] as $field) {
            if (blank($lostMetadata[$field] ?? null) || blank($foundMetadata[$field] ?? null)) {
                continue;
            }

            $normalizedLostValue = $this->normalizer->normalize((string) $lostMetadata[$field], 'exact');
            $normalizedFoundValue = $this->normalizer->normalize((string) $foundMetadata[$field], 'exact');

            if ($normalizedLostValue === '' || $normalizedFoundValue === '') {
                continue;
            }

            $matches = hash_equals($normalizedLostValue, $normalizedFoundValue);
            $reasonCodes[] = $field.($matches ? '_match' : '_mismatch');
            $fieldAgreements[$field] = ['match' => $matches];
        }

        return [
            'reason_codes' => $reasonCodes,
            'field_agreements' => $fieldAgreements,
        ];
    }
}
