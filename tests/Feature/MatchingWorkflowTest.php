<?php

namespace Tests\Feature;

use App\Enums\ReportKind;
use App\Enums\UserRole;
use App\Models\Claim;
use App\Models\MatchCandidate;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_owner_can_create_a_claim_on_a_match_candidate(): void
    {
        $owner = User::factory()->create();
        $report = Report::factory()->for($owner)->create([
            'kind' => ReportKind::Lost,
            'document_type' => 'passport',
            'status' => 'active',
            'holder_name' => 'Private Owner Name',
        ]);

        $otherOwner = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherReport = Report::factory()->for($otherOwner)->create([
            'kind' => ReportKind::Found,
            'document_type' => 'passport',
            'status' => 'active',
            'holder_name' => 'Private Owner Name',
        ]);

        $match = MatchCandidate::query()->create([
            'lost_report_id' => $report->id,
            'found_report_id' => $otherReport->id,
            'score' => 60,
            'reason_codes' => ['holder_name_exact'],
            'field_agreements' => ['holder_name' => ['match' => true]],
            'scoring_version' => '2.0',
            'status' => 'pending',
        ]);

        $this->assertTrue($owner->can('claim', $match));
        $this->assertFalse($otherUser->can('claim', $match));
        $this->assertTrue($owner->can('view', $match));

        $claim = Claim::query()->create([
            'match_candidate_id' => $match->id,
            'claimant_id' => $owner->id,
            'report_id' => $report->id,
            'status' => 'pending',
            'claimant_statement' => 'I have the original document.',
        ]);

        $this->assertSame('pending', $claim->status);
        $this->assertSame($report->id, $claim->report_id);
    }

    public function test_reviewer_role_can_view_claims_and_admin_can_review(): void
    {
        $reviewer = User::factory()->create(['role' => UserRole::Reviewer]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $owner = User::factory()->create();
        $report = Report::factory()->for($owner)->create();
        $match = MatchCandidate::query()->create([
            'lost_report_id' => $report->id,
            'found_report_id' => Report::factory()->for(User::factory()->create())->create()->id,
            'score' => 60,
            'reason_codes' => ['holder_name_exact'],
            'field_agreements' => ['holder_name' => ['match' => true]],
            'scoring_version' => '2.0',
            'status' => 'under_review',
        ]);

        $claim = Claim::query()->create([
            'match_candidate_id' => $match->id,
            'claimant_id' => $owner->id,
            'report_id' => $report->id,
            'status' => 'pending',
        ]);

        $this->assertTrue($reviewer->can('view', $claim));
        $this->assertTrue($admin->can('review', $claim));
    }
}
