<?php

namespace Tests\Feature;

use App\Enums\DocumentKind;
use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Claim;
use App\Models\MatchCandidate;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Operations Console');
        $response->assertSee('Total Lost-ID Reports');
        $response->assertSee('Total Found-ID Reports');
        $response->assertSee('Pending Verification');
        $response->assertSee('Returned and Resolved');
    }

    public function test_admin_dashboard_displays_pending_matches_and_claims(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $lostReport = Report::factory()->for($admin)->create([
            'kind' => ReportKind::Lost,
            'document_type' => DocumentKind::NiraId,
            'public_reference' => 'LDG-TESTLOST01',
            'status' => ReportStatus::Active,
            'holder_name' => 'Test document owner',
        ]);

        $foundReport = Report::factory()->for($admin)->create([
            'kind' => ReportKind::Found,
            'document_type' => DocumentKind::NiraId,
            'public_reference' => 'LDG-TESTFOUND01',
            'status' => ReportStatus::Active,
            'holder_name' => 'Test document owner',
        ]);

        $match = MatchCandidate::query()->create([
            'lost_report_id' => $lostReport->id,
            'found_report_id' => $foundReport->id,
            'score' => 60,
            'reason_codes' => ['holder_name_exact'],
            'status' => 'pending',
            'scoring_version' => '2.0',
        ]);

        $claim = Claim::query()->create([
            'match_candidate_id' => $match->id,
            'claimant_id' => $admin->id,
            'report_id' => $lostReport->id,
            'status' => 'pending',
            'claimant_statement' => 'This is my national ID card with exact NIN match.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('LDG-TESTLOST01');
        $response->assertSee('LDG-TESTFOUND01');
        $response->assertSee('60');
        $response->assertDontSee('60%');
        $response->assertSee('This is my national ID card with exact NIN match.');
    }

    public function test_reviewer_can_access_admin_dashboard(): void
    {
        $reviewer = User::factory()->create([
            'role' => UserRole::Reviewer,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($reviewer)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Operations Console');
    }

    public function test_admin_can_view_matches_queue_and_individual_match(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $lost = Report::factory()->for($admin)->create([
            'public_reference' => 'LDG-REVLOST99',
            'document_type' => DocumentKind::AcademicCertificate,
            'document_metadata' => [
                'institution_name' => 'Lost Report University',
                'certificate_type' => 'bachelors',
                'completion_year' => '2023',
            ],
        ]);

        $found = Report::factory()->for($admin)->create([
            'public_reference' => 'LDG-REVFND99',
            'document_type' => DocumentKind::AcademicCertificate,
            'document_metadata' => [
                'institution_name' => 'Found Report University',
                'certificate_type' => 'bachelors',
                'completion_year' => '2022',
            ],
        ]);

        $match = MatchCandidate::query()->create([
            'lost_report_id' => $lost->id,
            'found_report_id' => $found->id,
            'score' => 60,
            'reason_codes' => ['holder_name_exact'],
            'status' => 'pending',
            'scoring_version' => '2.0',
        ]);

        $indexRes = $this->actingAs($admin)->get(route('admin.matches.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('Match Review Queue');
        $indexRes->assertSee('LDG-REVLOST99');
        $indexRes->assertSee('60');
        $indexRes->assertDontSee('60%');

        $showRes = $this->actingAs($admin)->get(route('admin.matches.show', $match));
        $showRes->assertOk();
        $showRes->assertSee('Candidate Inspection and Verification');
        $showRes->assertSee('LDG-REVLOST99');
        $showRes->assertSee('LDG-REVFND99');
        $showRes->assertSee('60');
        $showRes->assertDontSee('60%');
        $showRes->assertSee('Lost Report University');
        $showRes->assertSee('Found Report University');
        $showRes->assertSee('2023');
        $showRes->assertSee('2022');
    }

    public function test_regular_user_cannot_view_private_academic_match_details(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $lost = Report::factory()->for($user)->create([
            'document_type' => DocumentKind::AcademicCertificate,
            'document_metadata' => ['institution_name' => 'Private University'],
        ]);
        $found = Report::factory()->for(User::factory()->create())->create([
            'document_type' => DocumentKind::AcademicCertificate,
            'document_metadata' => ['institution_name' => 'Private University'],
        ]);
        $match = MatchCandidate::query()->create([
            'lost_report_id' => $lost->id,
            'found_report_id' => $found->id,
            'score' => 60,
            'reason_codes' => ['holder_name_exact'],
            'status' => 'pending',
            'scoring_version' => '2.0',
        ]);

        $this->actingAs($user)->get(route('admin.matches.show', $match))->assertForbidden();
    }

    public function test_reviewer_can_see_private_student_id_academic_year_for_match_review(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $lost = Report::factory()->for(User::factory()->create())->create([
            'kind' => 'lost',
            'document_type' => DocumentKind::StudentId,
            'document_metadata' => [
                'institution_name' => 'Makerere University',
                'academic_year' => '2024/2025',
            ],
        ]);
        $found = Report::factory()->for(User::factory()->create())->create([
            'kind' => 'found',
            'document_type' => DocumentKind::StudentId,
            'document_metadata' => [
                'institution_name' => 'Makerere University',
                'academic_year' => '2024/2025',
            ],
        ]);
        $match = MatchCandidate::query()->create([
            'lost_report_id' => $lost->id,
            'found_report_id' => $found->id,
            'score' => 60,
            'reason_codes' => ['holder_name_exact', 'academic_year_match'],
            'field_agreements' => [
                'academic_year' => ['match' => true],
            ],
            'status' => 'pending',
            'scoring_version' => '2.2',
        ]);

        $this->actingAs($admin)->get(route('admin.matches.show', $match))
            ->assertOk()
            ->assertSee('Academic year (private)')
            ->assertSee('2024/2025');
    }

    public function test_reviewer_can_record_decision_on_match_candidate(): void
    {
        $reviewer = User::factory()->create([
            'role' => UserRole::Reviewer,
            'email_verified_at' => now(),
        ]);

        $lost = Report::factory()->for($reviewer)->create();
        $found = Report::factory()->for($reviewer)->create();

        $match = MatchCandidate::query()->create([
            'lost_report_id' => $lost->id,
            'found_report_id' => $found->id,
            'score' => 90,
            'status' => 'pending',
            'scoring_version' => '1.0',
        ]);

        $response = $this->actingAs($reviewer)->patch(route('admin.matches.update', $match), [
            'status' => 'confirmed',
            'reviewer_notes' => 'Confirmed matching passport numbers with immigration photo match.',
        ]);

        $response->assertRedirect(route('admin.matches.show', $match));
        $this->assertSame('confirmed', $match->fresh()->status);
        $this->assertSame($reviewer->id, $match->fresh()->reviewer_id);
        $this->assertSame('Confirmed matching passport numbers with immigration photo match.', $match->fresh()->reviewer_notes);
    }

    public function test_admin_can_update_report_status_and_claim(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $report = Report::factory()->for($admin)->create([
            'status' => ReportStatus::Active,
        ]);

        $claim = Claim::create([
            'claimant_id' => $admin->id,
            'report_id' => $report->id,
            'status' => 'pending',
            'claimant_statement' => 'Official recovery claim statement.',
        ]);

        $statusRes = $this->actingAs($admin)->patch(route('admin.reports.status', $report), [
            'status' => 'recovered',
            'admin_notes' => 'Handed over at Central Police Station.',
        ]);
        $statusRes->assertSessionHas('success');
        $this->assertSame(ReportStatus::Recovered, $report->fresh()->status);

        $claimRes = $this->actingAs($admin)->patch(route('admin.claims.update', $claim), [
            'status' => 'approved',
            'reviewer_notes' => 'Citizen identity confirmed via national biometric verification.',
        ]);
        $claimRes->assertSessionHas('success');
        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertNotNull($claim->fresh()->recovered_at);
    }
}
