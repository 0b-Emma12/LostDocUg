<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roleValues = "'user', 'reviewer', 'admin'";
        $kindValues = "'lost', 'found'";
        $documentValues = "'nira_id', 'passport', 'driving_licence', 'land_title', 'academic_certificate', 'student_id', 'bank_card', 'voter_document'";
        $statusValues = "'processing', 'active', 'under_review', 'matched', 'recovered', 'closed', 'rejected', 'expired'";

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER users_role_insert BEFORE INSERT ON users WHEN NEW.role NOT IN ({$roleValues}) BEGIN SELECT RAISE(ABORT, 'invalid user role'); END");
            DB::unprepared("CREATE TRIGGER users_role_update BEFORE UPDATE OF role ON users WHEN NEW.role NOT IN ({$roleValues}) BEGIN SELECT RAISE(ABORT, 'invalid user role'); END");
            DB::unprepared("CREATE TRIGGER reports_enum_insert BEFORE INSERT ON reports WHEN NEW.kind NOT IN ({$kindValues}) OR NEW.document_type NOT IN ({$documentValues}) OR NEW.status NOT IN ({$statusValues}) BEGIN SELECT RAISE(ABORT, 'invalid report enum'); END");
            DB::unprepared("CREATE TRIGGER reports_enum_update BEFORE UPDATE OF kind, document_type, status ON reports WHEN NEW.kind NOT IN ({$kindValues}) OR NEW.document_type NOT IN ({$documentValues}) OR NEW.status NOT IN ({$statusValues}) BEGIN SELECT RAISE(ABORT, 'invalid report enum'); END");
            DB::unprepared("CREATE TRIGGER audit_events_no_update BEFORE UPDATE ON audit_events BEGIN SELECT RAISE(ABORT, 'audit events are append-only'); END");
            DB::unprepared("CREATE TRIGGER audit_events_no_delete BEFORE DELETE ON audit_events BEGIN SELECT RAISE(ABORT, 'audit events are append-only'); END");
        } elseif (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ({$roleValues}))");
            DB::statement("ALTER TABLE reports ADD CONSTRAINT reports_kind_check CHECK (kind IN ({$kindValues}))");
            DB::statement("ALTER TABLE reports ADD CONSTRAINT reports_document_type_check CHECK (document_type IN ({$documentValues}))");
            DB::statement("ALTER TABLE reports ADD CONSTRAINT reports_status_check CHECK (status IN ({$statusValues}))");
            DB::unprepared("CREATE FUNCTION prevent_audit_event_mutation() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'audit events are append-only'; END; $$");
            DB::unprepared('CREATE TRIGGER audit_events_no_mutation BEFORE UPDATE OR DELETE ON audit_events FOR EACH ROW EXECUTE FUNCTION prevent_audit_event_mutation()');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS users_role_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS users_role_update');
            DB::unprepared('DROP TRIGGER IF EXISTS reports_enum_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS reports_enum_update');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_events_no_update');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_events_no_delete');
        } elseif (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_events_no_mutation ON audit_events');
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_audit_event_mutation()');
            DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
            DB::statement('ALTER TABLE reports DROP CONSTRAINT reports_kind_check');
            DB::statement('ALTER TABLE reports DROP CONSTRAINT reports_document_type_check');
            DB::statement('ALTER TABLE reports DROP CONSTRAINT reports_status_check');
        }
    }
};
