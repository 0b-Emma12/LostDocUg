<?php

namespace Tests\Feature;

use App\Enums\DocumentKind;
use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use App\Models\Claim;
use App\Models\MatchCandidate;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectorySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_does_not_publish_found_report_details(): void
    {
        $finder = User::factory()->create();

        Report::factory()->for($finder)->create([
            'kind' => ReportKind::Found,
            'document_type' => DocumentKind::AcademicCertificate,
            'document_metadata' => [
                'institution_name' => 'Private University',
                'certificate_type' => 'bachelors',
                'completion_year' => '2023',
            ],
            'holder_name' => 'Private Owner Name',
            'district' => 'Private District',
            'public_reference' => 'LDG-PRIVATE001',
            'status' => ReportStatus::Active,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('No public report search')
            ->assertDontSee('LDG-PRIVATE001')
            ->assertDontSee('Private Owner Name')
            ->assertDontSee('Private District')
            ->assertDontSee('Private University');
    }

    public function test_found_report_routes_require_a_verified_account(): void
    {
        $report = Report::factory()->for(User::factory()->create())->create([
            'kind' => ReportKind::Found,
            'status' => ReportStatus::Active,
        ]);

        $this->get(route('directory.index'))->assertRedirect(route('login'));
        $this->get(route('directory.show', $report))->assertRedirect(route('login'));
    }

    public function test_directory_entry_redirects_verified_users_to_their_private_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('directory.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_exact_private_name_match_is_visible_only_to_the_lost_report_owner(): void
    {
        $owner = User::factory()->create(['name' => 'Lost Report Account']);
        $finder = User::factory()->create(['name' => 'Found Report Account']);
        $otherUser = User::factory()->create();

        $lostReport = Report::factory()->for($owner)->create([
            'kind' => ReportKind::Lost,
            'document_type' => DocumentKind::AcademicCertificate,
            'holder_name' => 'Amina Nalwoga',
            'document_metadata' => [
                'institution_name' => 'Lost Private University',
                'certificate_type' => 'bachelors',
                'completion_year' => '2023',
            ],
            'status' => ReportStatus::Active,
        ]);

        $response = $this->actingAs($finder)->postJson(route('reports.store'), [
            'kind' => ReportKind::Found->value,
            'document_type' => DocumentKind::AcademicCertificate->value,
            'incident_date' => now()->subDay()->toDateString(),
            'district' => 'Kampala',
            'location_description' => 'Near a public stage',
            'station_name' => 'Central Police Station',
            'station_address' => 'Kampala Road, Kampala',
            'holder_name' => '  AMINA  NALWOGA ',
            'document_metadata' => [
                'institution_name' => 'Found Private University',
                'certificate_type' => 'bachelors',
                'completion_year' => '2023',
            ],
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '0123456789abcdef0123456789abcdef',
        ]);

        $response->assertCreated();
        $foundReport = Report::query()->findOrFail($response->json('report_id'));

        $this->assertDatabaseHas('match_candidates', [
            'lost_report_id' => $lostReport->id,
            'found_report_id' => $foundReport->id,
            'score' => 60,
            'status' => 'pending',
        ]);

        $match = MatchCandidate::query()
            ->where('lost_report_id', $lostReport->id)
            ->where('found_report_id', $foundReport->id)
            ->firstOrFail();

        $this->assertSame(false, $match->field_agreements['institution_name']['match']);
        $this->assertSame(true, $match->field_agreements['certificate_type']['match']);
        $this->assertSame(true, $match->field_agreements['completion_year']['match']);
        $this->assertContains('institution_name_mismatch', $match->reason_codes);

        $match = MatchCandidate::query()->where('lost_report_id', $lostReport->id)->firstOrFail();
        $this->assertStringNotContainsString('Amina', $match->getRawOriginal('field_agreements'));

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($foundReport->public_reference)
            ->assertSee('Possible matches')
            ->assertDontSee('Amina Nalwoga');

        $this->actingAs($otherUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($foundReport->public_reference);

        $this->actingAs($owner)
            ->get(route('directory.show', $foundReport))
            ->assertOk()
            ->assertSee('Limited match details')
            ->assertSee('Kampala')
            ->assertDontSee('Amina Nalwoga')
            ->assertDontSee('Found Private University')
            ->assertDontSee('Near a public stage');

        $this->actingAs($otherUser)
            ->get(route('directory.show', $foundReport))
            ->assertNotFound();
    }

    public function test_partial_non_identity_name_match_is_private_and_requires_manual_review(): void
    {
        $owner = User::factory()->create();
        $finder = User::factory()->create();
        $otherUser = User::factory()->create();

        $lostReport = Report::factory()->for($owner)->create([
            'kind' => ReportKind::Lost,
            'document_type' => DocumentKind::AcademicCertificate,
            'holder_name' => 'Amina Nalwoga',
            'status' => ReportStatus::Active,
        ]);

        $response = $this->actingAs($finder)->postJson(route('reports.store'), [
            'kind' => ReportKind::Found->value,
            'document_type' => DocumentKind::AcademicCertificate->value,
            'incident_date' => now()->subDay()->toDateString(),
            'station_name' => 'Central Police Station',
            'station_address' => 'Kampala Road, Kampala',
            'holder_name' => 'Amina Nalwoga Grace',
            'document_metadata' => [
                'institution_name' => 'Makerere University',
                'certificate_type' => 'bachelors',
            ],
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '2123456789abcdef0123456789abcdef',
        ]);

        $response->assertCreated();
        $foundReport = Report::query()->findOrFail($response->json('report_id'));
        $match = MatchCandidate::query()->where('lost_report_id', $lostReport->id)->firstOrFail();

        $this->assertSame(40, $match->score);
        $this->assertContains('holder_name_partial', $match->reason_codes);
        $this->assertContains('requires_manual_review', $match->reason_codes);
        $this->assertSame('partial', $match->field_agreements['holder_name']['match_type']);
        $this->assertStringNotContainsString('Amina', $match->getRawOriginal('field_agreements'));

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($foundReport->public_reference)
            ->assertSee('Possible matches');

        $this->actingAs($otherUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($foundReport->public_reference);
    }

    public function test_fuzzy_non_identity_name_match_is_recorded_as_a_suggestion(): void
    {
        $owner = User::factory()->create();
        $finder = User::factory()->create();

        $lostReport = Report::factory()->for($owner)->create([
            'kind' => ReportKind::Lost,
            'document_type' => DocumentKind::LandTitle,
            'holder_name' => 'Amina Nalwoga',
            'status' => ReportStatus::Active,
        ]);

        $response = $this->actingAs($finder)->postJson(route('reports.store'), [
            'kind' => ReportKind::Found->value,
            'document_type' => DocumentKind::LandTitle->value,
            'incident_date' => now()->subDay()->toDateString(),
            'station_name' => 'Central Police Station',
            'station_address' => 'Kampala Road, Kampala',
            'holder_name' => 'Amina Nalwogae',
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '3123456789abcdef0123456789abcdef',
        ]);

        $response->assertCreated();
        $match = MatchCandidate::query()->where('lost_report_id', $lostReport->id)->firstOrFail();

        $this->assertSame(40, $match->score);
        $this->assertContains('holder_name_fuzzy', $match->reason_codes);
        $this->assertSame('fuzzy', $match->field_agreements['holder_name']['match_type']);
        $this->assertSame('pending', $match->status);
    }

    public function test_identity_document_names_are_not_fuzzy_matched(): void
    {
        $owner = User::factory()->create();
        $finder = User::factory()->create();

        Report::factory()->for($owner)->create([
            'kind' => ReportKind::Lost,
            'document_type' => DocumentKind::Passport,
            'holder_name' => 'Amina Nalwoga',
            'status' => ReportStatus::Active,
        ]);

        $response = $this->actingAs($finder)->postJson(route('reports.store'), [
            'kind' => ReportKind::Found->value,
            'document_type' => DocumentKind::Passport->value,
            'incident_date' => now()->subDay()->toDateString(),
            'station_name' => 'Central Police Station',
            'station_address' => 'Kampala Road, Kampala',
            'holder_name' => 'Amina Nalwogae',
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '4123456789abcdef0123456789abcdef',
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('match_candidates', 0);
    }

    public function test_known_date_of_birth_mismatch_does_not_create_a_match(): void
    {
        $owner = User::factory()->create();
        $finder = User::factory()->create();

        Report::factory()->for($owner)->create([
            'kind' => ReportKind::Lost,
            'document_type' => DocumentKind::Passport,
            'holder_name' => 'Amina Nalwoga',
            'date_of_birth' => '1990-01-02',
            'status' => ReportStatus::Active,
        ]);

        $response = $this->actingAs($finder)->postJson(route('reports.store'), [
            'kind' => ReportKind::Found->value,
            'document_type' => DocumentKind::Passport->value,
            'incident_date' => now()->subDay()->toDateString(),
            'station_name' => 'Central Police Station',
            'station_address' => 'Kampala Road, Kampala',
            'holder_name' => 'Amina Nalwoga',
            'date_of_birth' => '1990-01-03',
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '1123456789abcdef0123456789abcdef',
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('match_candidates', 0);
    }

    public function test_only_the_matched_lost_report_owner_can_submit_a_claim(): void
    {
        $owner = User::factory()->create();
        $finder = User::factory()->create();
        $otherUser = User::factory()->create();

        $lostReport = Report::factory()->for($owner)->create([
            'kind' => ReportKind::Lost,
            'document_type' => DocumentKind::Passport,
            'holder_name' => 'Amina Nalwoga',
            'status' => ReportStatus::Active,
        ]);
        $foundReport = Report::factory()->for($finder)->create([
            'kind' => ReportKind::Found,
            'document_type' => DocumentKind::Passport,
            'holder_name' => 'Amina Nalwoga',
            'status' => ReportStatus::Active,
        ]);
        $candidate = MatchCandidate::query()->create([
            'lost_report_id' => $lostReport->id,
            'found_report_id' => $foundReport->id,
            'score' => 60,
            'reason_codes' => ['holder_name_exact', 'requires_manual_review'],
            'field_agreements' => ['holder_name' => ['match' => true]],
            'scoring_version' => '2.0',
            'status' => 'pending',
        ]);

        $this->actingAs($otherUser)
            ->post(route('directory.claim', $foundReport), [
                'claimant_statement' => 'A sufficiently long claim statement.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('claims', 0);

        $this->actingAs($owner)
            ->post(route('directory.claim', $foundReport), [
                'claimant_statement' => 'A sufficiently long claim statement.',
            ])
            ->assertRedirect(route('directory.show', $foundReport));

        $claim = Claim::query()->where('match_candidate_id', $candidate->id)->firstOrFail();
        $this->assertSame('A sufficiently long claim statement.', $claim->claimant_statement);
        $this->assertStringNotContainsString('A sufficiently long claim statement.', $claim->getRawOriginal('claimant_statement'));
    }
}
