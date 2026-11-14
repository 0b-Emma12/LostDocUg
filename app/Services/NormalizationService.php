<?php

namespace App\Services;

class NormalizationService
{
    public function normalize(string $value, string $matchType): string
    {
        $normalized = $this->unicodeNormalize($value);
        $normalized = $this->caseNormalize($normalized);
        $normalized = $this->whitespaceNormalize($normalized);

        return match ($matchType) {
            'exact' => $this->exactNormalize($normalized),
            'fuzzy' => $this->fuzzyNormalize($normalized),
            'partial' => $this->partialNormalize($normalized),
            default => $normalized,
        };
    }

    private function unicodeNormalize(string $value): string
    {
        return normalizer_normalize($value, \Normalizer::FORM_C) ?? $value;
    }

    private function caseNormalize(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    private function whitespaceNormalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function exactNormalize(string $value): string
    {
        return (string) preg_replace('/[^A-Z0-9]/', '', $value);
    }

    private function fuzzyNormalize(string $value): string
    {
        $value = (string) preg_replace('/[.,\-\/]/', ' ', $value);

        return $this->whitespaceNormalize($value);
    }

    private function partialNormalize(string $value): string
    {
        return $this->fuzzyNormalize($value);
    }
}
