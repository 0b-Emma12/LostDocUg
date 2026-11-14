<?php

namespace Tests\Feature;

use App\Enums\DocumentKind;
use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use App\Enums\StationDropoffStatus;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\Claim;
use App\Models\MatchCandidate;
use App\Models\Report;
use App\Models\ReportDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationDropoffWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_finder_submits_a_found_report_with_station_details_awaiting_confirmation(): void
    {
        $finder = User::factory()->create();

        $response = $this->actingAs($finder)->postJson(route('reports.store'), [
            'kind' => ReportKind::Found->value,
            'document_type' => DocumentKind::Passport->value,
            'incident_date' => now()->subDay()->toDateString(),
            'station_name' => 'Central Police Station',
            'station_address' => 'Kampala Road, Kampala',
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '1123456789abcdef0123456789abcdef',
        ]);

        $response->assertCreated();
        $report = Report::query()->findOrFail($response->json('report_id'));
        $this->assertSame('Central Police Station', $report->station_name);
        $this->assertSame('Kampala Road, Kampala', $report->station_address);
        $this->assertSame(StationDropoffStatus::AwaitingConfirmation, $report->station_dropoff_status);
        $this->assertNull($report->station_received_at);
        $this->assertArrayNotHasKey('station_name', $report->toArray());
        $this->assertArrayNotHasKey('station_address', $report->toArray());
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'station_dropoff_status' => StationDropoffStatus::AwaitingConfirmation->value,
        ]);
    }

    public function test_found_report_requires_station_name_and_address(): void
    {
        $finder = User::factory()->create();

        $this->actingAs($finder)->postJson(route('reports.store'), [
            'kind' => ReportKind::Found->value,
            'document_type' => DocumentKind::Passport->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '2123456789abcdef0123456789abcdef',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['station_name', 'station_address']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_report_draft_saves_station_details_only_for_found_reports(): void
    {
        $finder = User::factory()->create();

        $response = $this->actingAs($finder)->postJson(route('reports.drafts.save'), [
            'payload' => [
                'kind' => 'found',
                'station_name' => 'Central Police Station',
                'station_address' => 'Kampala Road, Kampala',
            ],
        ]);

        $response->assertOk();
        $draft = ReportDraft::query()->where('draft_key', $response->json('draft_key'))->firstOrFail();
        $this->assertSame('Central Police Station', $draft->payload['station_name']);
        $this->assertSame('Kampala Road, Kampala', $draft->payload['station_address']);
        $this->assertStringNotContainsString('Central Police Station', $draft->getRawOriginal('payload'));
    }

    public function test_reviewer_can_record_offline_station_receipt_confirmation(): void
    {
        $reviewer = User::factory()->create([
            'role' => UserRole::Reviewer,
            'email_verified_at' => now(),
        ]);
        $report = $this->foundReport();

        $response = $this->actingAs($reviewer)->patch(route('admin.reports.dropoff', $report), [
            'status' => StationDropoffStatus::Deposited->value,
            'confirmation' => '1',
        ]);

        $response->assertSessionHas('success');
        $this->assertSame(StationDropoffStatus::Deposited, $report->fresh()->station_dropoff_status);
        $this->assertSame($reviewer->id, $report->fresh()->station_received_by);
        $this->assertNotNull($report->fresh()->station_received_at);
        $this->assertSame(ReportStatus::Active, $report->fresh()->status);
        $this->assertDatabaseHas('audit_events', [
            'action' => 'station.receipt_confirmed_offline',
            'target_id' => $report->id,
            'user_id' => $reviewer->id,
        ]);
    }

    public function test_unconfirmed_receipt_does_not_mark_a_document_as_deposited(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $report = $this->foundReport();

        $this->actingAs($admin)->patch(route('admin.reports.dropoff', $report), [
            'status' => StationDropoffStatus::Deposited->value,
        ])
            ->assertSessionHasErrors('confirmation');

        $this->assertSame(StationDropoffStatus::AwaitingConfirmation, $report->fresh()->station_dropoff_status);
        $this->assertNull($report->fresh()->station_received_at);
        $this->assertDatabaseMissing('audit_events', [
            'action' => 'station.receipt_confirmed_offline',
            'target_id' => $report->id,
        ]);
    }

    public function test_regular_user_cannot_update_station_dropoff_status(): void
    {
        $user = User::factory()->create();
        $report = $this->foundReport();

        $this->actingAs($user)->patch(route('admin.reports.dropoff', $report), [
            'status' => StationDropoffStatus::Deposited->value,
            'confirmation' => '1',
        ])->assertForbidden();

        $this->assertSame(StationDropoffStatus::AwaitingConfirmation, $report->fresh()->station_dropoff_status);
    }

    public function test_document_cannot_be_collected_before_an_approved_claim_and_station_deposit(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $report = $this->foundReport();

        $this->actingAs($admin)->patch(route('admin.reports.dropoff', $report), [
            'status' => StationDropoffStatus::Collected->value,
            'confirmation' => '1',
        ])->assertSessionHasErrors('status');

        $this->assertSame(StationDropoffStatus::AwaitingConfirmation, $report->fresh()->station_dropoff_status);
        $this->assertNotSame(ReportStatus::Recovered, $report->fresh()->status);
        $this->assertNull($report->fresh()->station_collected_at);
    }

    public function test_admin_cannot_mark_a_found_station_report_recovered_without_collection_confirmation(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $report = $this->foundReport();

        $this->actingAs($admin)->patch(route('admin.reports.status', $report), [
            'status' => ReportStatus::Recovered->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(ReportStatus::Active, $report->fresh()->status);
        $this->assertSame(StationDropoffStatus::AwaitingConfirmation, $report->fresh()->station_dropoff_status);
    }

    public function test_staff_cannot_approve_a_station_claim_before_receipt_is_confirmed(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $owner = User::factory()->create();
        $report = $this->foundReport();
        $claim = Claim::query()->create([
            'claimant_id' => $owner->id,
            'report_id' => $report->id,
            'status' => 'pending',
            'claimant_statement' => 'I can describe how I lost this document.',
        ]);

        $this->actingAs($admin)->patch(route('admin.claims.update', $claim), [
            'status' => 'approved',
        ])->assertSessionHasErrors('status');

        $this->assertSame('pending', $claim->fresh()->status);
        $this->assertSame(ReportStatus::Active, $report->fresh()->status);
    }

    public function test_owner_sees_station_directions_only_after_receipt_and_claim_approval(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $owner = User::factory()->create();
        $finder = User::factory()->create();
        $lostReport = Report::factory()->for($owner)->create(['kind' => ReportKind::Lost]);
        $foundReport = Report::factory()->for($finder)->create([
            'kind' => ReportKind::Found,
            'status' => ReportStatus::Active,
            'station_name' => 'Central Police Station',
            'station_address' => 'Kampala Road, Kampala',
            'station_dropoff_status' => StationDropoffStatus::Deposited,
        ]);
        $match = MatchCandidate::query()->create([
            'lost_report_id' => $lostReport->id,
            'found_report_id' => $foundReport->id,
            'score' => 80,
            'reason_codes' => ['holder_name_exact'],
            'status' => 'pending',
            'scoring_version' => '1.0',
        ]);
        $claim = Claim::query()->create([
            'match_candidate_id' => $match->id,
            'claimant_id' => $owner->id,
            'report_id' => $foundReport->id,
            'status' => 'pending',
            'claimant_statement' => 'I can describe how I lost this document.',
        ]);

        $this->actingAs($owner)->get(route('directory.show', $foundReport))
            ->assertOk()
            ->assertDontSee('Central Police Station')
            ->assertDontSee('Kampala Road, Kampala');

        $this->actingAs($admin)->patch(route('admin.claims.update', $claim), [
            'status' => 'approved',
        ])->assertSessionHas('success');

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertNull($claim->fresh()->recovered_at);
        $this->assertSame(ReportStatus::Active, $foundReport->fresh()->status);

        $this->actingAs($owner)->get(route('directory.show', $foundReport))
            ->assertOk()
            ->assertSee('Central Police Station')
            ->assertSee('Kampala Road, Kampala')
            ->assertSee('Get directions')
            ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=Kampala%20Road%2C%20Kampala', false);
    }

    public function test_admin_records_collection_and_marks_the_report_recovered(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $owner = User::factory()->create();
        $report = $this->foundReport([
            'station_dropoff_status' => StationDropoffStatus::Deposited,
        ]);
        $claim = Claim::query()->create([
            'claimant_id' => $owner->id,
            'report_id' => $report->id,
            'status' => 'approved',
            'claimant_statement' => 'I can describe how I lost this document.',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.reports.dropoff', $report), [
            'status' => StationDropoffStatus::Collected->value,
            'confirmation' => '1',
        ]);

        $response->assertSessionHas('success');
        $this->assertSame(StationDropoffStatus::Collected, $report->fresh()->station_dropoff_status);
        $this->assertSame(ReportStatus::Recovered, $report->fresh()->status);
        $this->assertSame($admin->id, $report->fresh()->station_collected_by);
        $this->assertNotNull($report->fresh()->station_collected_at);
        $this->assertNotNull($claim->fresh()->recovered_at);
        $this->assertDatabaseHas('audit_events', [
            'action' => 'station.collection_confirmed_offline',
            'target_id' => $report->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_station_details_are_escaped_in_the_owner_match_page(): void
    {
        $owner = User::factory()->create();
        $finder = User::factory()->create();
        $lostReport = Report::factory()->for($owner)->create(['kind' => ReportKind::Lost]);
        $foundReport = Report::factory()->for($finder)->create([
            'kind' => ReportKind::Found,
            'status' => ReportStatus::Active,
            'station_name' => '<script>alert(1)</script>',
            'station_address' => 'Kampala Road',
            'station_dropoff_status' => StationDropoffStatus::Deposited,
        ]);
        $match = MatchCandidate::query()->create([
            'lost_report_id' => $lostReport->id,
            'found_report_id' => $foundReport->id,
            'score' => 80,
            'reason_codes' => ['holder_name_exact'],
            'status' => 'pending',
            'scoring_version' => '1.0',
        ]);
        Claim::query()->create([
            'match_candidate_id' => $match->id,
            'claimant_id' => $owner->id,
            'report_id' => $foundReport->id,
            'status' => 'approved',
            'claimant_statement' => 'I can describe how I lost this document.',
        ]);

        $this->actingAs($owner)->get(route('directory.show', $foundReport))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    private function foundReport(array $attributes = []): Report
    {
        $finder = User::factory()->create();

        return Report::factory()->for($finder)->create(array_merge([
            'kind' => ReportKind::Found,
            'status' => ReportStatus::Active,
            'station_name' => 'Central Police Station',
            'station_address' => 'Kampala Road, Kampala',
            'station_dropoff_status' => StationDropoffStatus::AwaitingConfirmation,
        ], $attributes));
    }
}
