<?php

use App\Enums\StationDropoffStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->string('station_name', 160)->nullable();
            $table->string('station_address', 300)->nullable();
            $table->enum('station_dropoff_status', array_column(StationDropoffStatus::cases(), 'value'))->nullable();
            $table->timestamp('station_received_at')->nullable();
            $table->foreignId('station_received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('station_collected_at')->nullable();
            $table->foreignId('station_collected_by')->nullable()->constrained('users')->nullOnDelete();
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->restoreReportEnumTriggers(true);
        }
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropForeign(['station_received_by']);
            $table->dropForeign(['station_collected_by']);
            $table->dropColumn([
                'station_name',
                'station_address',
                'station_dropoff_status',
                'station_received_at',
                'station_received_by',
                'station_collected_at',
                'station_collected_by',
            ]);
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->restoreReportEnumTriggers(false);
        }
    }

    private function restoreReportEnumTriggers(bool $includeStationStatus): void
    {
        $kindValues = "'lost', 'found'";
        $documentValues = "'nira_id', 'passport', 'driving_licence', 'land_title', 'academic_certificate', 'student_id', 'bank_card', 'voter_document'";
        $statusValues = "'processing', 'active', 'under_review', 'matched', 'recovered', 'closed', 'rejected', 'expired'";
        $stationStatusGuard = $includeStationStatus
            ? " OR (NEW.station_dropoff_status IS NOT NULL AND NEW.station_dropoff_status NOT IN ('awaiting_confirmation', 'deposited', 'collected'))"
            : '';
        $updatedColumns = $includeStationStatus
            ? 'kind, document_type, status, station_dropoff_status'
            : 'kind, document_type, status';

        DB::unprepared('DROP TRIGGER IF EXISTS reports_enum_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS reports_enum_update');
        DB::unprepared("CREATE TRIGGER reports_enum_insert BEFORE INSERT ON reports WHEN NEW.kind NOT IN ({$kindValues}) OR NEW.document_type NOT IN ({$documentValues}) OR NEW.status NOT IN ({$statusValues}){$stationStatusGuard} BEGIN SELECT RAISE(ABORT, 'invalid report enum'); END");
        DB::unprepared("CREATE TRIGGER reports_enum_update BEFORE UPDATE OF {$updatedColumns} ON reports WHEN NEW.kind NOT IN ({$kindValues}) OR NEW.document_type NOT IN ({$documentValues}) OR NEW.status NOT IN ({$statusValues}){$stationStatusGuard} BEGIN SELECT RAISE(ABORT, 'invalid report enum'); END");
    }
};
