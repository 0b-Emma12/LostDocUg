<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_and_update_an_active_report(): void
    {
        $owner = User::factory()->create();
        $report = Report::factory()->for($owner)->create(['status' => ReportStatus::Active]);

        $this->assertTrue($owner->can('view', $report));
        $this->assertTrue($owner->can('update', $report));
    }

    public function test_unrelated_user_and_unassigned_reviewer_cannot_view_a_report(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $reviewer = User::factory()->create();
        $reviewer->forceFill(['role' => UserRole::Reviewer])->save();
        $report = Report::factory()->for($owner)->create();

        $this->assertSame(UserRole::Reviewer, $reviewer->fresh()->role);
        $this->assertFalse($otherUser->can('view', $report));
        $this->assertFalse($reviewer->can('view', $report));
    }

    public function test_owner_cannot_update_a_recovered_report(): void
    {
        $owner = User::factory()->create();
        $report = Report::factory()->for($owner)->create(['status' => ReportStatus::Recovered]);

        $this->assertFalse($owner->can('update', $report));
    }

    public function test_owner_cannot_delete_a_matched_report(): void
    {
        $owner = User::factory()->create();
        $report = Report::factory()->for($owner)->create(['status' => ReportStatus::Matched]);

        $this->assertFalse($owner->can('delete', $report));
    }

    public function test_another_user_receives_not_found_for_a_report_detail(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $report = Report::factory()->for($owner)->create();

        $this->actingAs($otherUser)
            ->get(route('reports.show', $report))
            ->assertNotFound();
    }
}
