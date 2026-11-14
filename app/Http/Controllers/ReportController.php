<?php

namespace App\Http\Controllers;

use App\Enums\DocumentKind;
use App\Enums\ReportKind;
use App\Enums\StationDropoffStatus;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UploadDocumentImageRequest;
use App\Models\DocumentImage;
use App\Models\Report;
use App\Models\ReportDraft;
use App\Services\DocumentStorageService;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ReportController extends Controller
{
    public function create(Request $request): View
    {
        $kind = $request->query('kind', 'lost');
        abort_unless(in_array($kind, ['lost', 'found'], true), 404);

        $draft = $request->user()->reportDrafts()->latest('last_touched_at')->first();

        return view('reports.create', ['kind' => $kind, 'draftKey' => $draft?->draft_key]);
    }

    public function store(StoreReportRequest $request, DocumentStorageService $storage, MatchingService $matching): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $storedImageKeys = [];

        try {
            $report = DB::transaction(function () use ($data, $request, $user, $storage, &$storedImageKeys): Report {
                $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

                $existingReport = $user->reports()->where('idempotency_key', $data['idempotency_key'])->first();
                if ($existingReport) {
                    return $existingReport;
                }

                if (! empty($data['draft_key'])) {
                    $user->reportDrafts()->where('draft_key', $data['draft_key'])->firstOrFail();
                }

                $report = $user->reports()->create([
                    'kind' => $data['kind'],
                    'document_type' => $data['document_type'],
                    'incident_date' => $data['incident_date'],
                    'district' => $data['district'] ?? null,
                    'location_description' => $data['location_description'] ?? null,
                    'station_name' => $data['station_name'] ?? null,
                    'station_address' => $data['station_address'] ?? null,
                    'holder_name' => $data['holder_name'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'document_metadata' => $data['document_metadata'] ?? null,
                    'consent_version' => $data['consent_version'],
                    'expires_at' => now()->addDays(90),
                ]);
                if ($report->kind === ReportKind::Found) {
                    $report->station_dropoff_status = StationDropoffStatus::AwaitingConfirmation;
                    $report->save();
                }
                $report->forceFill(['idempotency_key' => $data['idempotency_key']])->save();

                foreach ($request->file('images', []) as $file) {
                    $image = $storage->storeImage($report, $file);
                    $storedImageKeys[] = $image->storage_key;
                }

                if (! empty($data['draft_key'])) {
                    $user->reportDrafts()->where('draft_key', $data['draft_key'])->delete();
                }

                return $report;
            });
        } catch (Throwable $exception) {
            $storage->deleteStoredObjects($storedImageKeys);
            throw $exception;
        }

        $matching->findMatches($report);

        if ($request->expectsJson()) {
            return response()->json([
                'report_id' => $report->id,
                'public_reference' => $report->public_reference,
                'status' => $report->status->value,
                'redirect' => route('reports.show', $report),
            ], 201);
        }

        return redirect()->route('reports.show', $report);
    }

    public function show(Request $request, Report $report): View
    {
        abort_unless($request->user()->can('view', $report), 404);

        return view('reports.show', ['report' => $report->load('images')]);
    }

    public function saveDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft_key' => ['nullable', 'uuid'],
            'payload' => ['required', 'array'],
            'payload.kind' => ['nullable', 'in:lost,found'],
            'payload.document_type' => ['nullable', 'string', 'in:'.implode(',', array_map(fn (DocumentKind $kind): string => $kind->value, DocumentKind::cases()))],
            'payload.incident_date' => ['nullable', 'date'],
            'payload.district' => ['nullable', 'string', 'max:100'],
            'payload.location_description' => ['nullable', 'string', 'max:500'],
            'payload.station_name' => ['exclude_unless:payload.kind,found', 'nullable', 'string', 'max:160'],
            'payload.station_address' => ['exclude_unless:payload.kind,found', 'nullable', 'string', 'max:300'],
            'payload.document_metadata' => [
                'nullable',
                'array:institution_name,certificate_type,completion_year,academic_year',
                'prohibited_unless:payload.document_type,academic_certificate,student_id',
            ],
            'payload.document_metadata.institution_name' => ['nullable', 'string', 'max:200'],
            'payload.document_metadata.certificate_type' => [
                'nullable',
                'prohibited_unless:payload.document_type,academic_certificate',
                Rule::in(['bachelors', 'masters', 'diploma', 'certificate', 'transcript']),
            ],
            'payload.document_metadata.completion_year' => [
                'nullable',
                'prohibited_unless:payload.document_type,academic_certificate',
                'integer',
                'digits:4',
                'between:1900,'.now()->year,
            ],
            'payload.document_metadata.academic_year' => [
                'nullable',
                'prohibited_unless:payload.document_type,student_id',
                'string',
                'max:20',
            ],
            'payload.step' => ['nullable', 'integer', 'between:0,3'],
        ]);

        $user = $request->user();
        $draftKey = $validated['draft_key'] ?? (string) Str::uuid();
        $draft = $user->reportDrafts()->where('draft_key', $draftKey)->first();

        if (! $draft && ReportDraft::query()->where('draft_key', $draftKey)->exists()) {
            abort(404);
        }

        $draft ??= $user->reportDrafts()->make(['draft_key' => $draftKey]);
        $draft->payload = array_intersect_key($validated['payload'], array_flip([
            'kind',
            'document_type',
            'incident_date',
            'district',
            'location_description',
            'station_name',
            'station_address',
            'document_metadata',
            'step',
        ]));
        $draft->last_touched_at = now();
        $draft->save();

        return response()->json(['draft_key' => $draft->draft_key]);
    }

    public function loadDraft(Request $request, string $draftKey): JsonResponse
    {
        $draft = $request->user()->reportDrafts()->where('draft_key', $draftKey)->firstOrFail();

        return response()->json(['draft_key' => $draft->draft_key, 'payload' => $draft->payload]);
    }

    public function uploadImage(UploadDocumentImageRequest $request, Report $report, DocumentStorageService $storage): RedirectResponse
    {
        $storage->storeImage($report, $request->file('image'));

        return back()->with('status', 'image-uploaded');
    }

    public function image(Request $request, Report $report, DocumentImage $image)
    {
        abort_unless($request->user()->can('viewImage', $report), 404);
        abort_unless($image->report_id === $report->id, 404);

        return Storage::disk(config('filesystems.private_documents_disk'))
            ->response($image->storage_key, basename($image->storage_key), [
                'Content-Type' => $image->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ], 'inline');
    }

    public function destroyImage(Request $request, Report $report, DocumentImage $image, DocumentStorageService $storage): RedirectResponse
    {
        abort_unless($request->user()->can('update', $report), 404);
        abort_unless($image->report_id === $report->id, 404);
        $storage->deleteImage($image);

        return back()->with('status', 'image-deleted');
    }
}
