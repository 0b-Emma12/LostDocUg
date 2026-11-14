@extends('layouts.admin')

@section('title', 'Match Verification Dossier #'.$match->id.' | LostDocUg Admin')
@section('page_title', 'Match Dossier #'.$match->id)

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-eyebrow">
                <a href="{{ route('admin.matches.index') }}" class="back-link-text">← Back to Match Queue</a>
                <span class="bc-sep">/</span>
                <span>MATCH DOSSIER #{{ $match->id }}</span>
            </div>
            <h1 class="admin-page-title">Candidate Inspection and Verification</h1>
            <p class="admin-page-desc">Private comparison of Lost Report #{{ $match->lost_report_id }} and Found Report #{{ $match->found_report_id }}.</p>
        </div>
        <div class="header-action-group">
            <span class="status-indicator-badge badge-lg {{ $match->status }}">
                <span class="dot"></span>
                <span>{{ ucfirst(str_replace('_', ' ', $match->status)) }}</span>
            </span>
        </div>
    </div>

    {{-- Score Banner --}}
    @php
        $scoreTone = $match->score >= 80 ? 'high' : ($match->score >= 60 ? 'mid' : 'low');
    @endphp
    <div class="match-score-banner {{ $scoreTone }}">
        <div class="score-radial-box">
            <span class="radial-number">{{ $match->score }}</span>
            <span class="radial-sub">Review priority</span>
        </div>
        <div class="score-details-box">
            <div class="score-headline">
                <strong>Possible match — human review required</strong>
                <span class="scoring-version">Engine v{{ $match->scoring_version ?? '1.0' }}</span>
            </div>
            <p class="score-desc">
                The recorded private signals generated this suggestion. Agreement on an optional date of birth can increase review priority; no name, document identifier, or OCR result proves ownership or authenticity.
            </p>
            <div class="signals-row">
                <span class="signals-label">Triggered Signals:</span>
                @forelse ($match->reason_codes ?? [] as $code)
                    <span class="signal-tag {{ $scoreTone }}">{{ ucwords(str_replace('_', ' ', $code)) }}</span>
                @empty
                    <span class="signal-tag muted">None specified</span>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Side-by-Side Comparison Dossier --}}
    <div class="dossier-comparison-grid">
        {{-- Left: Lost Report --}}
        <div class="dossier-card lost">
            <div class="dossier-card-header">
                <div class="dossier-badge lost">LOST FILING</div>
                <h3 class="dossier-ref">{{ $match->lostReport?->public_reference ?? 'Report #'.$match->lost_report_id }}</h3>
                <span class="dossier-type">{{ ucwords(str_replace('_', ' ', $match->lostReport?->document_type?->value ?? 'Document')) }}</span>
            </div>
            <div class="dossier-body">
                <div class="dossier-field">
                    <span class="field-label">Document Owner</span>
                    <strong class="field-val">{{ $match->lostReport?->user?->name ?? 'Anonymous User' }}</strong>
                </div>
                <div class="dossier-field">
                    <span class="field-label">Contact Channel</span>
                    <span class="field-val">{{ $match->lostReport?->user?->phone ?? $match->lostReport?->user?->email ?? 'Protected' }}</span>
                </div>
                <div class="dossier-field">
                    <span class="field-label">Incident District</span>
                    <span class="field-val">{{ $match->lostReport?->district ?? 'Uganda' }}</span>
                </div>
                <div class="dossier-field">
                    <span class="field-label">Date of Loss</span>
                    <span class="field-val">{{ $match->lostReport?->incident_date?->format('M d, Y') ?? 'Unspecified' }}</span>
                </div>
                @php($lostMetadata = $match->lostReport?->document_metadata ?? [])
                @foreach ([
                    'Institution (private)' => $lostMetadata['institution_name'] ?? null,
                    'Certificate type (private)' => isset($lostMetadata['certificate_type']) ? str($lostMetadata['certificate_type'])->replace('_', ' ')->title() : null,
                    'Completion year (private)' => $lostMetadata['completion_year'] ?? null,
                    'Academic year (private)' => $lostMetadata['academic_year'] ?? null,
                ] as $label => $value)
                    @if (filled($value))
                        <div class="dossier-field">
                            <span class="field-label">{{ $label }}</span>
                            <span class="field-val">{{ $value }}</span>
                        </div>
                    @endif
                @endforeach
                <div class="dossier-field">
                    <span class="field-label">Location Details</span>
                    <p class="field-notes">{{ $match->lostReport?->location_description ?: 'No additional location narrative provided.' }}</p>
                </div>
                <div class="dossier-field">
                    <span class="field-label">Current Lifecycle Status</span>
                    <span class="status-indicator-badge {{ $match->lostReport?->status?->value ?? 'active' }}">
                        <span class="dot"></span>
                        <span>{{ ucfirst($match->lostReport?->status?->value ?? 'active') }}</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Right: Found Report --}}
        <div class="dossier-card found">
            <div class="dossier-card-header">
                <div class="dossier-badge found">FOUND DOCUMENT</div>
                <h3 class="dossier-ref">{{ $match->foundReport?->public_reference ?? 'Report #'.$match->found_report_id }}</h3>
                <span class="dossier-type">{{ ucwords(str_replace('_', ' ', $match->foundReport?->document_type?->value ?? 'Document')) }}</span>
            </div>
            <div class="dossier-body">
                <div class="dossier-field">
                    <span class="field-label">Discovered By (Finder)</span>
                    <strong class="field-val">{{ $match->foundReport?->user?->name ?? 'Citizen Finder' }}</strong>
                </div>
                <div class="dossier-field">
                    <span class="field-label">Finder Contact</span>
                    <span class="field-val">{{ $match->foundReport?->user?->phone ?? $match->foundReport?->user?->email ?? 'Protected' }}</span>
                </div>
                <div class="dossier-field">
                    <span class="field-label">Found District</span>
                    <span class="field-val">{{ $match->foundReport?->district ?? 'Uganda' }}</span>
                </div>
                <div class="dossier-field">
                    <span class="field-label">Date Discovered</span>
                    <span class="field-val">{{ $match->foundReport?->incident_date?->format('M d, Y') ?? 'Unspecified' }}</span>
                </div>
                @php($foundMetadata = $match->foundReport?->document_metadata ?? [])
                @foreach ([
                    'Institution (private)' => $foundMetadata['institution_name'] ?? null,
                    'Certificate type (private)' => isset($foundMetadata['certificate_type']) ? str($foundMetadata['certificate_type'])->replace('_', ' ')->title() : null,
                    'Completion year (private)' => $foundMetadata['completion_year'] ?? null,
                    'Academic year (private)' => $foundMetadata['academic_year'] ?? null,
                ] as $label => $value)
                    @if (filled($value))
                        <div class="dossier-field">
                            <span class="field-label">{{ $label }}</span>
                            <span class="field-val">{{ $value }}</span>
                        </div>
                    @endif
                @endforeach
                <div class="dossier-field">
                    <span class="field-label">Custody & Recovery Location</span>
                    <p class="field-notes">{{ $match->foundReport?->location_description ?: 'No recovery location details specified.' }}</p>
                </div>
                <div class="dossier-field">
                    <span class="field-label">Current Lifecycle Status</span>
                    <span class="status-indicator-badge {{ $match->foundReport?->status?->value ?? 'active' }}">
                        <span class="dot"></span>
                        <span>{{ ucfirst($match->foundReport?->status?->value ?? 'active') }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Field Agreement Matrix --}}
    @if (! empty($match->field_agreements))
        <div class="admin-main-card mt-24">
            <div class="admin-card-header">
                <div>
                    <h3 class="admin-card-title">Private match signals</h3>
                    <p class="admin-card-subtitle">Names and dates are not copied into match metadata. Independently review the underlying reports before making a decision.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Field Attribute</th>
                            <th>Agreement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($match->field_agreements as $field => $data)
                            <tr>
                                <td>
                                    <strong>{{ ucwords(str_replace('_', ' ', $field)) }}</strong>
                                </td>
                                <td>
                                    @if (! empty($data['match']))
                                        <span class="match-check-pill match">
                                            <x-icon name="check" class="mcp-ic" /> Match Agreement
                                        </span>
                                    @else
                                        <span class="match-check-pill mismatch">
                                            <x-icon name="x" class="mcp-ic" /> Discrepancy
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Claims & Identity Proof Section --}}
    @if ($match->claims->isNotEmpty())
        <div class="admin-main-card mt-24">
            <div class="admin-card-header">
                <div>
                    <h3 class="admin-card-title">Citizen Ownership Claims ({{ $match->claims->count() }})</h3>
                    <p class="admin-card-subtitle">Identity claims filed by individuals stating ownership of this document.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Claim ID</th>
                            <th>Claimant</th>
                            <th>Statement Narrative</th>
                            <th>Status</th>
                            <th>Filed Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($match->claims as $claim)
                            <tr>
                                <td>
                                    <span class="mono-badge">#{{ $claim->id }}</span>
                                </td>
                                <td>
                                    <strong>{{ $claim->claimant?->name ?? 'User #'.$claim->claimant_id }}</strong>
                                    <div class="doc-sub">{{ $claim->claimant?->phone ?? $claim->claimant?->email }}</div>
                                </td>
                                <td>
                                    <p class="admin-claim-narrative">
                                        {{ $claim->claimant_statement ?: 'No statement narrative provided.' }}
                                    </p>
                                </td>
                                <td>
                                    <span class="status-indicator-badge {{ $claim->status }}">
                                        <span class="dot"></span>
                                        <span>{{ ucfirst($claim->status) }}</span>
                                    </span>
                                </td>
                                <td>
                                    <span class="time-sub">{{ $claim->created_at?->format('M d, Y H:i') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Reviewer Decision Form Panel --}}
    <div class="admin-main-card reviewer-decision-card mt-24">
        <div class="admin-card-header">
            <div>
                <h3 class="admin-card-title">Official Reviewer Decision</h3>
                <p class="admin-card-subtitle">Record your verification determination. Changes are signed and entered into the immutable audit trail.</p>
            </div>
            @if ($match->reviewer)
                <div class="doc-sub text-right">
                    Last reviewed by <strong>{{ $match->reviewer->name }}</strong> on {{ $match->reviewed_at?->format('M d, Y H:i') }}
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.matches.update', $match) }}" class="decision-form">
            @csrf
            @method('PATCH')

            <div class="form-group">
                <label class="form-label">Review Status Determination</label>
                <div class="decision-radio-grid">
                    <label class="decision-option {{ $match->status === 'confirmed' ? 'is-selected' : '' }}">
                        <input type="radio" name="status" value="confirmed" {{ $match->status === 'confirmed' ? 'checked' : '' }} required>
                        <div class="opt-content">
                            <span class="opt-title text-emerald">Confirm Match</span>
                            <small class="opt-desc">Lost and found documents belong to the same citizen. Ready for owner notification and safe recovery.</small>
                        </div>
                    </label>

                    <label class="decision-option {{ $match->status === 'under_review' ? 'is-selected' : '' }}">
                        <input type="radio" name="status" value="under_review" {{ $match->status === 'under_review' ? 'checked' : '' }}>
                        <div class="opt-content">
                            <span class="opt-title text-amber">Keep Under Review</span>
                            <small class="opt-desc">Needs secondary evidence, further OCR scan, or contact verification before finalizing.</small>
                        </div>
                    </label>

                    <label class="decision-option {{ $match->status === 'dismissed' ? 'is-selected' : '' }}">
                        <input type="radio" name="status" value="dismissed" {{ $match->status === 'dismissed' ? 'checked' : '' }}>
                        <div class="opt-content">
                            <span class="opt-title text-rose">Dismiss Candidate</span>
                            <small class="opt-desc">Determined to be a false positive match. Will be removed from the active queue.</small>
                        </div>
                    </label>

                    <label class="decision-option {{ $match->status === 'pending' ? 'is-selected' : '' }}">
                        <input type="radio" name="status" value="pending" {{ $match->status === 'pending' ? 'checked' : '' }}>
                        <div class="opt-content">
                            <span class="opt-title">Pending</span>
                            <small class="opt-desc">Reset status back to initial unreviewed state.</small>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group mt-20">
                <label for="reviewer_notes" class="form-label">Official Reviewer Notes / Evidence Rationale</label>
                <textarea
                    id="reviewer_notes"
                    name="reviewer_notes"
                    rows="4"
                    class="decision-textarea"
                    placeholder="Provide justification for this verification decision (e.g., 'Verified NIN matches National ID format; claimant confirmed birth date matching OCR extract.')."
                >{{ old('reviewer_notes', $match->reviewer_notes) }}</textarea>
                <span class="field-hint">These notes are visible to certified reviewers and recorded in the tamper-evident audit ledger.</span>
            </div>

            <div class="decision-actions">
                <button type="submit" class="button button-primary">
                    Commit Decision to Audit Trail
                </button>
                <a href="{{ route('admin.matches.index') }}" class="button button-secondary">
                    Cancel & Return
                </a>
            </div>
        </form>
    </div>
@endsection
