<?php

namespace Tests\Feature;

use App\Enums\DocumentKind;
use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use App\Enums\StationDropoffStatus;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_saves_enum_values_and_generates_its_own_reference(): void
    {
        $user = User::factory()->create();
        $report = $user->reports()->create([
            'kind' => ReportKind::Lost,
            'document_type' => DocumentKind::NiraId,
            'incident_date' => '2026-10-20',
            'consent_version' => 'lostdocug-privacy-v2',
            'public_reference' => 'LDG-ATTACKER',
            'status' => ReportStatus::Recovered,
        ]);

        $this->assertMatchesRegularExpression('/^LDG-[A-Z0-9]{10}$/', $report->public_reference);
        $this->assertSame(ReportKind::Lost, $report->fresh()->kind);
        $this->assertSame(DocumentKind::NiraId, $report->fresh()->document_type);
        $this->assertSame(ReportStatus::Processing, $report->fresh()->status);
        $this->assertDatabaseHas('reports', ['id' => $report->id, 'user_id' => $user->id]);
    }

    public function test_database_rejects_an_unsupported_report_kind(): void
    {
        $user = User::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('reports')->insert([
            'public_reference' => 'LDG-INVALID1',
            'user_id' => $user->id,
            'kind' => 'public_search',
            'document_type' => DocumentKind::NiraId->value,
            'status' => ReportStatus::Processing->value,
            'incident_date' => '2026-10-20',
            'consent_version' => 'lostdocug-privacy-v2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_an_unsupported_station_dropoff_status(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->for($user)->create([
            'kind' => ReportKind::Found,
            'station_dropoff_status' => StationDropoffStatus::AwaitingConfirmation,
        ]);

        $this->expectException(QueryException::class);

        DB::table('reports')
            ->where('id', $report->id)
            ->update(['station_dropoff_status' => 'unverified']);
    }

    public function test_database_rejects_an_unsupported_user_role(): void
    {
        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'name' => 'Invalid Role',
            'email' => 'invalid-role@example.test',
            'password' => 'hashed-password',
            'role' => 'superuser',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_user_role_cannot_be_changed_by_mass_assignment(): void
    {
        $user = User::factory()->create();
        $user->refresh();
        $user->fill(['role' => UserRole::Admin->value]);

        $this->assertSame(UserRole::User, $user->role);
    }

    public function test_audit_events_cannot_be_updated_or_deleted_through_the_database(): void
    {
        $event = AuditEvent::create(['action' => 'report.created']);

        try {
            DB::table('audit_events')->where('id', $event->id)->update(['action' => 'changed']);
            $this->fail('The database allowed an audit event update.');
        } catch (QueryException) {
            $this->assertDatabaseHas('audit_events', ['id' => $event->id, 'action' => 'report.created']);
        }

        $this->expectException(QueryException::class);
        DB::table('audit_events')->where('id', $event->id)->delete();
    }

    public function test_audit_event_remains_after_its_actor_account_is_deleted(): void
    {
        $user = User::factory()->create();
        $event = AuditEvent::create(['user_id' => $user->id, 'action' => 'report.created']);

        $user->delete();

        $this->assertDatabaseHas('audit_events', ['id' => $event->id, 'user_id' => $user->id]);
    }
}
