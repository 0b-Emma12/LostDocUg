<?php

namespace Tests\Feature;

use App\Enums\DocumentKind;
use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\DocumentImage;
use App\Models\Report;
use App\Models\ReportDraft;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_cannot_open_report_creation(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('reports.create'))->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_render_the_report_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('reports.create', ['kind' => 'lost']))
            ->assertOk()
            ->assertSee('Document category')
            ->assertSee('Keep identifying numbers private')
            ->assertSee('On-device OCR may suggest a name')
            ->assertSee('Given name(s)')
            ->assertSee('Date of birth')
            ->assertSee('Bank cards need a different route')
            ->assertSee('Institution name')
            ->assertSee('Certificate type')
            ->assertSee('Academic year')
            ->assertSee('student numbers or grades')
            ->assertSee('human review required');

        $this->actingAs($user)->get(route('reports.create', ['kind' => 'found']))
            ->assertOk()
            ->assertSee('Police station drop-off')
            ->assertSee('Station address or location');
    }

    public function test_dashboard_lists_only_the_current_users_reports(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownReport = Report::factory()->for($user)->create();
        $otherReport = Report::factory()->for($otherUser)->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee($ownReport->public_reference)
            ->assertDontSee($otherReport->public_reference);
    }

    public function test_verified_user_can_save_and_load_only_their_encrypted_draft(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $draftKey = (string) Str::uuid();
        $payload = [
            'kind' => 'lost',
            'document_type' => 'academic_certificate',
            'location_description' => 'Near a public stage',
            'document_metadata' => [
                'institution_name' => 'Makerere University',
                'certificate_type' => 'bachelors',
                'completion_year' => '2023',
            ],
            'step' => 1,
            'holder_name' => 'Name from draft',
            'date_of_birth' => '2001-03-06',
            'secret_identifier' => 'must-not-persist',
        ];

        $response = $this->actingAs($user)->postJson(route('reports.drafts.save'), [
            'draft_key' => $draftKey,
            'payload' => $payload,
        ]);

        $response->assertOk()->assertJsonPath('draft_key', $draftKey);
        $draft = ReportDraft::query()->where('draft_key', $draftKey)->firstOrFail();
        $this->assertSame([
            'kind' => 'lost',
            'document_type' => 'academic_certificate',
            'location_description' => 'Near a public stage',
            'document_metadata' => [
                'institution_name' => 'Makerere University',
                'certificate_type' => 'bachelors',
                'completion_year' => '2023',
            ],
            'step' => 1,
        ], $draft->payload);
        $this->assertStringNotContainsString('Near a public stage', $draft->getRawOriginal('payload'));
        $this->assertStringNotContainsString('Makerere University', $draft->getRawOriginal('payload'));
        $this->actingAs($otherUser)->getJson(route('reports.drafts.load', $draftKey))->assertNotFound();
    }

    public function test_draft_rejects_sensitive_academic_metadata_keys(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('reports.drafts.save'), [
            'payload' => [
                'kind' => 'lost',
                'document_type' => 'academic_certificate',
                'document_metadata' => [
                    'institution_name' => 'Makerere University',
                    'student_number' => 'STU-123456',
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payload.document_metadata');

        $this->assertDatabaseCount('report_drafts', 0);
    }

    public function test_student_id_academic_year_is_saved_in_the_encrypted_draft(): void
    {
        $user = User::factory()->create();
        $draftKey = (string) Str::uuid();

        $response = $this->actingAs($user)->postJson(route('reports.drafts.save'), [
            'draft_key' => $draftKey,
            'payload' => [
                'kind' => 'lost',
                'document_type' => 'student_id',
                'document_metadata' => [
                    'institution_name' => 'Makerere University',
                    'academic_year' => '2024/2025',
                ],
            ],
        ]);

        $response->assertOk()->assertJsonPath('draft_key', $draftKey);
        $draft = ReportDraft::query()->where('draft_key', $draftKey)->firstOrFail();
        $this->assertSame([
            'kind' => 'lost',
            'document_type' => 'student_id',
            'document_metadata' => [
                'institution_name' => 'Makerere University',
                'academic_year' => '2024/2025',
            ],
        ], $draft->payload);
        $this->assertStringNotContainsString('2024/2025', $draft->getRawOriginal('payload'));
    }

    public function test_user_cannot_claim_another_accounts_draft_key(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $draftKey = (string) Str::uuid();
        $owner->reportDrafts()->create([
            'draft_key' => $draftKey,
            'payload' => ['kind' => 'lost'],
            'last_touched_at' => now(),
        ]);

        $this->actingAs($otherUser)->postJson(route('reports.drafts.save'), [
            'draft_key' => $draftKey,
            'payload' => ['kind' => 'found'],
        ])->assertNotFound();

        $this->assertDatabaseCount('report_drafts', 1);
    }

    public function test_verified_user_can_submit_report_and_repeat_request_is_idempotent(): void
    {
        $user = User::factory()->create();
        $payload = [
            'kind' => ReportKind::Lost->value,
            'document_type' => DocumentKind::Passport->value,
            'incident_date' => now()->subDay()->toDateString(),
            'district' => 'Kampala',
            'location_description' => 'At a public stage',
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '0123456789abcdef0123456789abcdef',
        ];

        $response = $this->actingAs($user)->postJson(route('reports.store'), $payload);

        $response->assertCreated()->assertJsonPath('status', ReportStatus::Processing->value);
        $reportId = $response->json('report_id');
        $this->postJson(route('reports.store'), $payload)->assertCreated()->assertJsonPath('report_id', $reportId);
        $this->assertDatabaseCount('reports', 1);
    }

    public function test_verified_user_can_store_encrypted_private_details_on_their_report(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $payload = [
            'kind' => ReportKind::Lost->value,
            'document_type' => DocumentKind::Passport->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '1123456789abcdef0123456789abcdef',
            'holder_name' => 'Amina Nalwoga',
            'date_of_birth' => '2001-03-06',
        ];

        $response = $this->actingAs($owner)->postJson(route('reports.store'), $payload);

        $response->assertCreated();
        $report = Report::query()->findOrFail($response->json('report_id'));
        $this->assertSame('Amina Nalwoga', $report->holder_name);
        $this->assertStringNotContainsString('Amina Nalwoga', $report->getRawOriginal('holder_name'));
        $this->assertSame('2001-03-06', $report->date_of_birth);
        $this->assertStringNotContainsString('2001-03-06', $report->getRawOriginal('date_of_birth'));
        $this->assertArrayNotHasKey('holder_name', $report->toArray());
        $this->assertArrayNotHasKey('date_of_birth', $report->toArray());
        $this->actingAs($owner)->get(route('reports.show', $report))->assertOk()->assertSee('Amina Nalwoga')->assertSee('6 Mar 2001');
        $this->actingAs($otherUser)->get(route('reports.show', $report))->assertNotFound();
    }

    public function test_academic_metadata_is_encrypted_and_visible_only_to_the_report_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $payload = [
            'kind' => ReportKind::Lost->value,
            'document_type' => DocumentKind::AcademicCertificate->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '4123456789abcdef0123456789abcdef',
            'holder_name' => 'Amina Nalwoga',
            'document_metadata' => [
                'institution_name' => 'Makerere University',
                'certificate_type' => 'bachelors',
                'completion_year' => '2023',
            ],
        ];

        $response = $this->actingAs($owner)->postJson(route('reports.store'), $payload);

        $response->assertCreated();
        $report = Report::query()->findOrFail($response->json('report_id'));
        $this->assertSame($payload['document_metadata'], $report->document_metadata);
        $this->assertStringNotContainsString('Makerere University', $report->getRawOriginal('document_metadata'));
        $this->assertArrayNotHasKey('document_metadata', $report->toArray());
        $this->actingAs($owner)->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Institution (private)')
            ->assertSee('Makerere University')
            ->assertSee('2023')
            ->assertSee('Certificate type (private)');
        $this->actingAs($otherUser)->get(route('reports.show', $report))->assertNotFound();
    }

    public function test_student_id_report_stores_private_institution_and_academic_year(): void
    {
        $user = User::factory()->create();
        $payload = [
            'kind' => ReportKind::Lost->value,
            'document_type' => DocumentKind::StudentId->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '9123456789abcdef0123456789abcdef',
            'document_metadata' => [
                'institution_name' => 'Makerere University',
                'academic_year' => '2024/2025',
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('reports.store'), $payload);

        $response->assertCreated();
        $report = Report::query()->findOrFail($response->json('report_id'));
        $this->assertSame([
            'institution_name' => 'Makerere University',
            'academic_year' => '2024/2025',
        ], $report->document_metadata);
        $this->assertStringNotContainsString('Makerere University', $report->getRawOriginal('document_metadata'));
        $this->actingAs($user)->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Academic year (private)')
            ->assertSee('2024/2025');
    }

    public function test_report_rejects_academic_metadata_for_a_passport(): void
    {
        $user = User::factory()->create();
        $payload = [
            'kind' => ReportKind::Lost->value,
            'document_type' => DocumentKind::Passport->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '5123456789abcdef0123456789abcdef',
            'document_metadata' => ['institution_name' => 'Makerere University'],
        ];

        $this->actingAs($user)->postJson(route('reports.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors.document_metadata.0', 'Academic details are only accepted for academic certificates and student IDs.');
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_student_id_metadata_rejects_certificate_type_and_completion_year(): void
    {
        $user = User::factory()->create();
        $payload = [
            'kind' => ReportKind::Lost->value,
            'document_type' => DocumentKind::StudentId->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '8123456789abcdef0123456789abcdef',
            'document_metadata' => [
                'institution_name' => 'Makerere University',
                'academic_year' => '2024/2025',
                'certificate_type' => 'bachelors',
                'completion_year' => '2023',
            ],
        ];

        $this->actingAs($user)->postJson(route('reports.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['document_metadata.certificate_type', 'document_metadata.completion_year']);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_report_rejects_student_numbers_in_academic_metadata(): void
    {
        $user = User::factory()->create();
        $payload = [
            'kind' => ReportKind::Lost->value,
            'document_type' => DocumentKind::AcademicCertificate->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '6123456789abcdef0123456789abcdef',
            'document_metadata' => [
                'institution_name' => 'Makerere University',
                'student_number' => 'STU-123456',
            ],
        ];

        $this->actingAs($user)->postJson(route('reports.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors.document_metadata.0', 'Only institution, certificate type, completion year, and academic year may be submitted as academic metadata.');
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_academic_certificate_requires_institution_and_certificate_type(): void
    {
        $user = User::factory()->create();
        $payload = [
            'kind' => ReportKind::Lost->value,
            'document_type' => DocumentKind::AcademicCertificate->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '7123456789abcdef0123456789abcdef',
        ];

        $response = $this->actingAs($user)->postJson(route('reports.store'), $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['document_metadata.institution_name', 'document_metadata.certificate_type']);
        $errors = $response->json('errors');
        $this->assertSame(
            'Enter the institution name for this academic document.',
            $errors['document_metadata.institution_name'][0],
        );
        $this->assertSame(
            'Choose a certificate type for this academic certificate.',
            $errors['document_metadata.certificate_type'][0],
        );
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_student_id_requires_its_institution_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('reports.store'), [
            'kind' => ReportKind::Lost->value,
            'document_type' => DocumentKind::StudentId->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '7123456789abcdef0123456789abcdef',
            'document_metadata' => ['academic_year' => '2024/2025'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('document_metadata.institution_name');
        $errors = $response->json('errors');
        $this->assertSame(
            'Enter the institution name for this academic document.',
            $errors['document_metadata.institution_name'][0],
        );
        $this->assertDatabaseCount('reports', 0);
    }

    #[DataProvider('documentTypesThatDoNotCollectDateOfBirth')]
    public function test_document_categories_that_do_not_collect_date_of_birth_reject_it(DocumentKind $documentType): void
    {
        $user = User::factory()->create();
        $payload = [
            'kind' => ReportKind::Lost->value,
            'document_type' => $documentType->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '7123456789abcdef0123456789abcdef',
            'date_of_birth' => '1990-01-02',
        ];

        $this->actingAs($user)->postJson(route('reports.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors.date_of_birth.0', 'Date of birth is collected only for National ID and passport reports.');
        $this->assertDatabaseCount('reports', 0);
    }

    public static function documentTypesThatDoNotCollectDateOfBirth(): array
    {
        return [
            'driving permit' => [DocumentKind::DrivingLicence],
            'land title' => [DocumentKind::LandTitle],
            'academic certificate' => [DocumentKind::AcademicCertificate],
            'student ID' => [DocumentKind::StudentId],
            'voter document' => [DocumentKind::VoterDocument],
        ];
    }

    public function test_report_date_of_birth_rejects_future_dates(): void
    {
        $user = User::factory()->create();
        $payload = [
            'kind' => ReportKind::Lost->value,
            'document_type' => DocumentKind::Passport->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '3123456789abcdef0123456789abcdef',
            'date_of_birth' => now()->addDay()->toDateString(),
        ];

        $this->actingAs($user)->postJson(route('reports.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_of_birth');
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_report_holder_name_rejects_identifying_numbers(): void
    {
        $user = User::factory()->create();
        $payload = [
            'kind' => ReportKind::Lost->value,
            'document_type' => DocumentKind::Passport->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '2123456789abcdef0123456789abcdef',
            'holder_name' => 'UG123456789012',
        ];

        $this->actingAs($user)->postJson(route('reports.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('holder_name');
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_bank_card_image_submission_is_rejected_server_side(): void
    {
        $user = User::factory()->create();
        $payload = [
            'kind' => 'found',
            'document_type' => DocumentKind::BankCard->value,
            'incident_date' => now()->subDay()->toDateString(),
            'consent_version' => 'lostdocug-privacy-v2',
            'idempotency_key' => '0123456789abcdef0123456789abcdef',
            'date_of_birth' => '1990-01-01',
            'images' => [UploadedFile::fake()->image('card.png')],
        ];

        $this->actingAs($user)->postJson(route('reports.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['images', 'date_of_birth']);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_private_image_is_reencoded_stored_and_visible_only_to_owner(): void
    {
        Storage::fake('private_documents');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $report = Report::factory()->for($owner)->create(['document_type' => DocumentKind::Passport]);
        $file = UploadedFile::fake()->image('passport.png', 120, 80);

        $this->actingAs($owner)->post(route('reports.images.store', $report), ['image' => $file])->assertRedirect();
        $image = DocumentImage::query()->where('report_id', $report->id)->firstOrFail();
        $this->assertSame('image/webp', $image->mime_type);
        Storage::disk('private_documents')->assertExists($image->storage_key);
        $this->actingAs($owner)->get(route('reports.images.show', [$report, $image]))->assertOk();
        $this->actingAs($otherUser)->get(route('reports.images.show', [$report, $image]))->assertNotFound();
    }

    public function test_registration_sends_verification_and_does_not_allow_role_input(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'New User',
            'email' => 'new-user@example.test',
            'password' => 'Correct-Horse-Battery-1!',
            'password_confirmation' => 'Correct-Horse-Battery-1!',
            'role' => UserRole::Admin->value,
        ]);

        $user = User::query()->where('email', 'new-user@example.test')->firstOrFail();
        $this->assertSame(UserRole::User, $user->role);
        $this->assertNull($user->email_verified_at);
        $response->assertRedirect(route('verification.notice'));
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
