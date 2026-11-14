<?php

namespace Database\Factories;

use App\Enums\DocumentKind;
use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => fake()->randomElement(ReportKind::cases()),
            'document_type' => fake()->randomElement(DocumentKind::cases()),
            'status' => ReportStatus::Active,
            'incident_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'district' => fake()->city(),
            'location_description' => fake()->sentence(),
            'consent_version' => 'lostdocug-privacy-v2',
            'expires_at' => now()->addMonths(6),
        ];
    }
}
