@extends('layouts.admin')

@section('title', 'Admin Dashboard | LostDocUg Operations')
@section('page_title', 'Dashboard')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-eyebrow">
                <span class="pulse-dot"></span>
                <span>LostDocUg Operations Console</span>
            </div>
            <h1 class="admin-page-title">Report and Match Review</h1>
            <p class="admin-page-desc">Review private lost and found reports, possible matches, and claims that require human assessment.</p>
        </div>
        <div class="header-action-group">
            <a href="{{ route('admin.matches.index') }}" class="button button-primary">
                <span>Match Review Queue ({{ $activeMatches }})</span>
            </a>
        </div>
    </div>

    @if ($highPriorityMatches > 0 || $pendingClaims > 0)
        <div class="admin-callout-banner">
            <div class="callout-icon-box">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="callout-body">
                <strong>Attention Required: Urgent Pipeline Tasks</strong>
                <p>
                    There are <strong>{{ $highPriorityMatches }}</strong> high-priority suggestions (score ≥80) and <strong>{{ $pendingClaims }}</strong> claims awaiting human review. Scores rank review priority; they do not prove ownership.
                </p>
            </div>
            <div class="callout-actions">
                @if ($highPriorityMatches > 0)
                    <a href="{{ route('admin.matches.index', ['min_score' => 80]) }}" class="button button-secondary compact">
                        Review Suggestions
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- 4 Core Summary Metric Cards (Database-Driven) --}}
    <div class="admin-kpi-grid">
        {{-- Metric 1: Total Lost-ID Reports --}}
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Total Lost-ID Reports</span>
                <div class="kpi-icon-wrap rose">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number">{{ number_format($lostReports) }}</span>
                <span class="kpi-trend alert">Missing</span>
            </div>
            <div class="kpi-footer">
                <span class="kpi-breakdown">Filed by citizen owners</span>
                <span class="kpi-tag">{{ $reports > 0 ? round(($lostReports / $reports) * 100) : 0 }}% of registry</span>
            </div>
        </div>

        {{-- Metric 2: Total Found-ID Reports --}}
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Total Found-ID Reports</span>
                <div class="kpi-icon-wrap emerald">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number">{{ number_format($foundReports) }}</span>
                <span class="kpi-trend positive">{{ $activeReports }} active in custody</span>
            </div>
            <div class="kpi-footer">
                <span class="kpi-breakdown">Discovered across Uganda</span>
                <span class="kpi-tag">{{ $reports > 0 ? round(($foundReports / $reports) * 100) : 0 }}% of registry</span>
            </div>
        </div>

        {{-- Metric 3: Pending Verification --}}
        <div class="kpi-card highlight-amber">
            <div class="kpi-header">
                <span class="kpi-title">Pending Verification</span>
                <div class="kpi-icon-wrap amber">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number">{{ number_format($pendingVerificationCount) }}</span>
                <span class="kpi-trend {{ $pendingVerificationCount > 0 ? 'alert' : 'neutral' }}">
                    Awaiting Action
                </span>
            </div>
            <div class="kpi-footer">
                <span class="kpi-breakdown">{{ $activeMatches }} matches · {{ $pendingClaims }} claims</span>
                <a href="{{ route('admin.matches.index') }}" class="kpi-link">Inspect →</a>
            </div>
        </div>

        {{-- Metric 4: IDs Successfully Returned / Resolved --}}
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Returned and Resolved</span>
                <div class="kpi-icon-wrap teal">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number">{{ number_format($recoveredReports) }}</span>
                <span class="kpi-trend positive">{{ $recoveryRate }}% Success</span>
            </div>
            <div class="kpi-footer">
                <div class="kpi-progress-bar">
                    <div class="kpi-progress-fill" style="width: {{ min(100, $recoveryRate) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Multi-Tab Workspace --}}
    <div
        class="admin-main-card"
        x-data="{ activeTab: ['records', 'matches', 'claims', 'analytics', 'audit'].includes(window.location.hash.slice(1)) ? window.location.hash.slice(1) : 'records' }"
        x-init="$nextTick(() => { const tab = window.location.hash.slice(1); if (['matches', 'claims', 'analytics', 'audit'].includes(tab)) { document.getElementById(tab)?.scrollIntoView({ block: 'start' }) } })"
    >
        <div class="admin-card-header">
            <div class="tab-controls" role="tablist">
                <button
                    type="button"
                    id="records-tab"
                    role="tab"
                    :aria-selected="activeTab === 'records'"
                    aria-controls="records"
                    class="tab-btn"
                    :class="{ 'is-active': activeTab === 'records' }"
                    @click="activeTab = 'records'; window.history.replaceState(null, '', window.location.pathname + window.location.search)"
                >
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    <span>ID Records Management</span>
                    <span class="tab-pill">{{ $manageReports->total() }}</span>
                </button>

                <button
                    type="button"
                    id="matches-tab"
                    role="tab"
                    :aria-selected="activeTab === 'matches'"
                    aria-controls="matches"
                    class="tab-btn"
                    :class="{ 'is-active': activeTab === 'matches' }"
                    @click="activeTab = 'matches'; window.history.replaceState(null, '', window.location.pathname + window.location.search + '#matches')"
                >
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span>Match Queue</span>
                    <span class="tab-pill">{{ $activeMatches }}</span>
                </button>

                <button
                    type="button"
                    id="claims-tab"
                    role="tab"
                    :aria-selected="activeTab === 'claims'"
                    aria-controls="claims"
                    class="tab-btn"
                    :class="{ 'is-active': activeTab === 'claims' }"
                    @click="activeTab = 'claims'; window.history.replaceState(null, '', window.location.pathname + window.location.search + '#claims')"
                >
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Ownership Claims</span>
                    <span class="tab-pill {{ $pendingClaims > 0 ? 'alert' : '' }}">{{ $pendingClaims }}</span>
                </button>

                <button
                    type="button"
                    id="analytics-tab"
                    role="tab"
                    :aria-selected="activeTab === 'analytics'"
                    aria-controls="analytics"
                    class="tab-btn"
                    :class="{ 'is-active': activeTab === 'analytics' }"
                    @click="activeTab = 'analytics'; window.history.replaceState(null, '', window.location.pathname + window.location.search + '#analytics')"
                >
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                    </svg>
                    <span>Analytics & Hotspots</span>
                </button>

                <button
                    type="button"
                    id="audit-tab"
                    role="tab"
                    :aria-selected="activeTab === 'audit'"
                    aria-controls="audit"
                    class="tab-btn"
                    :class="{ 'is-active': activeTab === 'audit' }"
                    @click="activeTab = 'audit'; window.history.replaceState(null, '', window.location.pathname + window.location.search + '#audit')"
                >
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                    </svg>
                    <span>Audit Trail</span>
                    <span class="tab-pill">{{ $recentEvents->count() }}</span>
                </button>
            </div>
        </div>

        {{-- TAB 1: Complete ID Records Management Table --}}
        <div id="records" role="tabpanel" aria-labelledby="records-tab" x-show="activeTab === 'records'" class="tab-pane" x-cloak>
            {{-- Filter Bar for Records Table --}}
            <div class="admin-filter-bar">
                <form method="GET" action="{{ route('admin.dashboard') }}" class="ul-directory-filter-form m-0">
                    <div class="filter-inputs-grid admin-filter-5col">
                        <div>
                            <input
                                type="text"
                                name="report_q"
                                value="{{ request('report_q') }}"
                                placeholder="Search by reference, user, district..."
                                class="ul-filter-input"
                            >
                        </div>
                        <div>
                            <select name="report_kind" class="ul-filter-select">
                                <option value="all">All Filing Types</option>
                                <option value="lost" {{ request('report_kind') === 'lost' ? 'selected' : '' }}>Lost Reports</option>
                                <option value="found" {{ request('report_kind') === 'found' ? 'selected' : '' }}>Found Reports</option>
                            </select>
                        </div>
                        <div>
                            <select name="report_type" class="ul-filter-select">
                                <option value="all">All Document Types</option>
                                <option value="nira_id" {{ request('report_type') === 'nira_id' ? 'selected' : '' }}>National ID</option>
                                <option value="passport" {{ request('report_type') === 'passport' ? 'selected' : '' }}>Passport</option>
                                <option value="driving_licence" {{ request('report_type') === 'driving_licence' ? 'selected' : '' }}>Driving Licence</option>
                                <option value="academic_certificate" {{ request('report_type') === 'academic_certificate' ? 'selected' : '' }}>Academic Certificate</option>
                                <option value="land_title" {{ request('report_type') === 'land_title' ? 'selected' : '' }}>Land Title</option>
                            </select>
                        </div>
                        <div>
                            <select name="report_status" class="ul-filter-select">
                                <option value="all">All Statuses</option>
                                <option value="active" {{ request('report_status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="processing" {{ request('report_status') === 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="under_review" {{ request('report_status') === 'under_review' ? 'selected' : '' }}>Under Review</option>
                                <option value="matched" {{ request('report_status') === 'matched' ? 'selected' : '' }}>Matched</option>
                                <option value="recovered" {{ request('report_status') === 'recovered' ? 'selected' : '' }}>Recovered</option>
                                <option value="rejected" {{ request('report_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="admin-filter-actions">
                            <button type="submit" class="button button-primary compact">Filter</button>
                            @if (request()->hasAny(['report_q', 'report_kind', 'report_type', 'report_status']))
                                <a href="{{ route('admin.dashboard') }}" class="button button-secondary compact">Reset</a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            @if ($manageReports->isEmpty())
                <div class="empty-state-panel">
                    <h3>No Records Matching Filter</h3>
                    <p>Try clearing filters or search terms.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Filing Type</th>
                                <th>Document Category</th>
                                <th>Holder (Authorized View)</th>
                                <th>Location & Date</th>
                                <th>Police Drop-Off</th>
                                <th>Status</th>
                                <th class="text-right">Administrative Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($manageReports as $rec)
                                <tr>
                                    <td>
                                        <strong class="mono-badge brand">{{ $rec->public_reference }}</strong>
                                        @if ($rec->images->isNotEmpty())
                                            <span class="ul-img-count-badge" title="Document images attached"><x-icon name="camera" class="adm-cam-ic" /> {{ $rec->images->count() }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="kind-tag {{ $rec->kind->value }}">
                                            {{ strtoupper($rec->kind->value) }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $rec->category_label }}</strong>
                                    </td>
                                    <td>
                                        <div class="doc-summary-cell">
                                            <span>{{ $rec->user?->name ?? 'Citizen' }}</span>
                                            <span class="doc-sub">{{ $rec->user?->phone ?? $rec->user?->email }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div><x-icon name="location" class="adm-loc-ic" /> {{ $rec->district ?? 'Uganda' }}</div>
                                        <span class="doc-sub">{{ $rec->incident_date?->format('M d, Y') ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @if ($rec->station_dropoff_status)
                                            <div class="admin-station-summary">
                                                <strong>{{ $rec->station_name }}</strong>
                                                <span class="doc-sub">{{ $rec->station_address }}</span>
                                                <span class="status-indicator-badge {{ $rec->station_dropoff_status->value }}">
                                                    <span class="dot"></span>
                                                    <span>{{ ucfirst(str_replace('_', ' ', $rec->station_dropoff_status->value)) }}</span>
                                                </span>
                                                @if ($rec->station_received_at)
                                                    <span class="doc-sub">Receipt confirmed {{ $rec->station_received_at->format('M d, Y H:i') }}</span>
                                                @endif
                                                @if ($rec->station_collected_at)
                                                    <span class="doc-sub">Collection confirmed {{ $rec->station_collected_at->format('M d, Y H:i') }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="doc-sub">Not provided</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-indicator-badge {{ $rec->status->value }}">
                                            <span class="dot"></span>
                                            <span>{{ ucfirst(str_replace('_', ' ', $rec->status->value)) }}</span>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="admin-table-actions">
                                            {{-- Status Update Quick Dropdown Form --}}
                                            <form method="POST" action="{{ route('admin.reports.status', $rec) }}" class="m-0 d-inline-flex">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="ul-select-mini" onchange="this.form.submit()" title="Change report status">
                                                    <option value="" disabled selected>Change Status...</option>
                                                    <option value="active">Mark Active</option>
                                                    <option value="under_review">Under Review</option>
                                                    <option value="matched">Mark Matched</option>
                                                    @if ($rec->station_dropoff_status === null || $rec->station_dropoff_status->value === 'collected')
                                                        <option value="recovered">Mark Recovered / Resolved</option>
                                                    @endif
                                                    <option value="rejected">Reject Submission</option>
                                                </select>
                                            </form>

                                            @if ($rec->station_dropoff_status?->value === 'awaiting_confirmation')
                                                <form method="POST" action="{{ route('admin.reports.dropoff', $rec) }}" class="admin-station-action">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="deposited">
                                                    <label><input type="checkbox" name="confirmation" value="1" required> Police station receipt confirmed offline</label>
                                                    <button type="submit" class="button button-primary compact">Confirm receipt</button>
                                                </form>
                                            @elseif ($rec->station_dropoff_status?->value === 'deposited' && $rec->approved_claims_count > 0)
                                                <form method="POST" action="{{ route('admin.reports.dropoff', $rec) }}" class="admin-station-action">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="collected">
                                                    <label><input type="checkbox" name="confirmation" value="1" required> Station collection confirmed offline</label>
                                                    <button type="submit" class="button button-primary compact">Confirm collection</button>
                                                </form>
                                            @elseif ($rec->station_dropoff_status?->value === 'deposited')
                                                <span class="doc-sub">Collection update is available after a claim is approved.</span>
                                            @elseif ($rec->station_dropoff_status?->value === 'collected')
                                                <span class="doc-sub">Station collection complete</span>
                                            @endif
                                            @error('confirmation')
                                                <span class="field-error" role="alert">{{ $message }}</span>
                                            @enderror
                                            @error('status')
                                                <span class="field-error" role="alert">{{ $message }}</span>
                                            @enderror

                                            @if ($rec->kind->value === 'found')
                                                <a href="{{ route('admin.matches.index') }}" class="button button-secondary compact" title="Review private match suggestions">
                                                    Match queue
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($manageReports->hasPages())
                    <div class="pagination-footer">
                        {{ $manageReports->links() }}
                    </div>
                @endif
            @endif
        </div>

        {{-- TAB 2: Match Review Queue --}}
        <div id="matches" role="tabpanel" aria-labelledby="matches-tab" x-show="activeTab === 'matches'" class="tab-pane" x-cloak>
            @if ($pendingMatchCandidates->isEmpty())
                <div class="empty-state-panel">
                    <div class="empty-icon-wrap"><x-icon name="check" /></div>
                    <h3>Match Queue Clear</h3>
                    <p>No document match candidates currently require reviewer action.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Match ID</th>
                                <th>Lost Document</th>
                                <th>Found Document</th>
                                <th>Confidence</th>
                                <th>Reason Codes</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingMatchCandidates as $match)
                                <tr>
                                    <td><span class="mono-badge">#{{ $match->id }}</span></td>
                                    <td>
                                        <div class="doc-summary-cell">
                                            <strong class="ref-link">{{ $match->lostReport?->public_reference ?? 'Report #'.$match->lost_report_id }}</strong>
                                            <span class="doc-sub">{{ $match->lostReport?->district }} · Owner: {{ $match->lostReport?->user?->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="doc-summary-cell">
                                            <strong class="ref-link">{{ $match->foundReport?->public_reference ?? 'Report #'.$match->found_report_id }}</strong>
                                            <span class="doc-sub">Found: {{ $match->foundReport?->district }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="score-pill-box {{ $match->score >= 80 ? 'high' : ($match->score >= 60 ? 'mid' : 'low') }}">
                                            <span class="score-num">{{ $match->score }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tag-cloud">
                                            @foreach ($match->reason_codes ?? [] as $code)
                                                <span class="signal-tag">{{ ucwords(str_replace('_', ' ', $code)) }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-indicator-badge {{ $match->status }}">
                                            <span class="dot"></span>
                                            <span>{{ ucfirst($match->status) }}</span>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.matches.show', $match) }}" class="button button-primary compact">
                                            Inspect Match
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- TAB 3: Claim Requests Management --}}
        <div id="claims" role="tabpanel" aria-labelledby="claims-tab" x-show="activeTab === 'claims'" class="tab-pane claims-pane" x-cloak>
            <section class="claims-workspace" aria-labelledby="claims-heading">
                <div class="claims-workspace-header">
                    <div>
                        <div class="admin-eyebrow">
                            <span class="pulse-dot"></span>
                            <span>PRIVATE REVIEW QUEUE</span>
                        </div>
                        <h2 id="claims-heading" class="claims-heading">Ownership claims</h2>
                        <p class="claims-description">Review each request carefully before deciding whether a document can be returned.</p>
                    </div>
                    <div class="claims-open-count" aria-live="polite">
                        <strong>{{ number_format($pendingClaims) }}</strong>
                        <span>awaiting review</span>
                    </div>
                </div>

                <div class="claims-summary-grid" aria-label="Claim queue summary">
                    <article class="claims-summary-card">
                        <span class="claims-summary-label">All submissions</span>
                        <strong>{{ number_format($totalClaims) }}</strong>
                        <span class="claims-summary-caption">Claims received</span>
                    </article>
                    <article class="claims-summary-card is-pending">
                        <span class="claims-summary-label">Pending review</span>
                        <strong>{{ number_format($pendingClaims) }}</strong>
                        <span class="claims-summary-caption">Require a decision</span>
                    </article>
                    <article class="claims-summary-card is-approved">
                        <span class="claims-summary-label">Approved</span>
                        <strong>{{ number_format($approvedClaims) }}</strong>
                        <span class="claims-summary-caption">Marked for recovery</span>
                    </article>
                </div>

                <div class="claims-review-note">
                    <x-icon name="shield" />
                    <p><strong>Human verification required.</strong> Matching signals and claimant statements do not prove ownership or authenticity. Review the submitted evidence and follow the approved verification process before deciding.</p>
                </div>

                <div class="claims-list-heading">
                    <div>
                        <h3>Recent claims</h3>
                        <p>Latest submissions and their review status.</p>
                    </div>
                    <span class="claims-list-count">Showing {{ $pendingClaimsList->count() }} of {{ number_format($totalClaims) }}</span>
                </div>

                @if ($pendingClaimsList->isEmpty())
                    <div class="empty-state-panel claims-empty-state">
                        <div class="empty-icon-wrap"><x-icon name="shield" /></div>
                        <h3>No claims submitted yet</h3>
                        <p>New ownership claims will appear here for private human review.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="modern-table claims-table">
                            <thead>
                                <tr>
                                    <th>Claimant</th>
                                    <th>Document record</th>
                                    <th>Claim statement</th>
                                    <th>Status and submitted</th>
                                    <th class="text-right">Review</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pendingClaimsList as $claim)
                                    <tr>
                                        <td>
                                            <div class="doc-summary-cell">
                                                <strong>{{ $claim->claimant?->name ?? 'User #'.$claim->claimant_id }}</strong>
                                                <span class="doc-sub">{{ $claim->claimant?->phone ?? $claim->claimant?->email }}</span>
                                                <span class="mono-badge">Claim #{{ $claim->id }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="ref-link">{{ $claim->report?->public_reference ?? 'Record unavailable' }}</span>
                                            <div class="doc-sub">{{ $claim->report?->category_label ?? 'Document' }}</div>
                                        </td>
                                        <td>
                                            <div class="statement-clamp" title="{{ $claim->claimant_statement }}">
                                                {{ $claim->claimant_statement ?: 'No statement provided.' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="claims-status-cell">
                                                <span class="status-indicator-badge {{ $claim->status }}">
                                                    <span class="dot"></span>
                                                    <span>{{ ucfirst($claim->status) }}</span>
                                                </span>
                                                <span class="time-sub">{{ $claim->created_at?->diffForHumans() }}</span>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            @if (in_array($claim->status, ['pending', 'reviewing'], true))
                                                <form method="POST" action="{{ route('admin.claims.update', $claim) }}" class="claims-actions">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" name="status" value="approved" class="button button-primary compact btn-approve" title="Approve claim and mark recovered">
                                                        Approve
                                                    </button>
                                                    <button type="submit" name="status" value="rejected" class="button button-secondary compact btn-danger-text" title="Reject unverified claim">
                                                        Reject
                                                    </button>
                                                </form>
                                            @else
                                                <span class="claims-decision-recorded">Decision recorded</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        {{-- TAB 4: Analytics & Hotspots --}}
        <div id="analytics" role="tabpanel" aria-labelledby="analytics-tab" x-show="activeTab === 'analytics'" class="tab-pane" x-cloak>
            <div class="analytics-dual-grid">
                <div class="analytics-card">
                    <div class="card-inner-header">
                        <h3>Filing Volume by Document Category</h3>
                        <p>Distribution of lost and found civil records</p>
                    </div>
                    @php
                        $maxCount = max(count($reportsByType) > 0 ? max(array_values($reportsByType)) : 1, 1);
                    @endphp
                    <div class="bar-chart-list">
                        @foreach ($reportsByType as $type => $count)
                            <div class="bar-chart-row">
                                <div class="bar-info">
                                    <span class="bar-name">{{ ucwords(str_replace('_', ' ', $type)) }}</span>
                                    <span class="bar-val">{{ $count }} ({{ $reports > 0 ? round(($count / $reports) * 100, 1) : 0 }}%)</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-thumb emerald" style="width: {{ min(100, round(($count / $maxCount) * 100)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="analytics-card">
                    <div class="card-inner-header">
                        <h3>Regional Loss & Finding Hotspots</h3>
                        <p>Top administrative districts by document turn-in activity</p>
                    </div>
                    @php
                        $maxDistrictCount = max($topDistricts->max('count') ?? 1, 1);
                    @endphp
                    <div class="bar-chart-list">
                        @foreach ($topDistricts as $dist)
                            <div class="bar-chart-row">
                                <div class="bar-info">
                                    <span class="bar-name">{{ $dist->district }} District</span>
                                    <span class="bar-val">{{ $dist->count }} filings</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-thumb amber" style="width: {{ min(100, round(($dist->count / $maxDistrictCount) * 100)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 5: Audit Log --}}
        <div id="audit" role="tabpanel" aria-labelledby="audit-tab" x-show="activeTab === 'audit'" class="tab-pane" x-cloak>
            <div class="audit-timeline-wrap">
                <div class="audit-header">
                    <h3>Tamper-Evident Security Log</h3>
                    <p>Append-only audit ledger recording every operational action for privacy compliance.</p>
                </div>
                <div class="audit-stream-list">
                    @forelse ($recentEvents as $event)
                        <div class="audit-event-row">
                            <div class="audit-icon-ring"><span class="ring-dot"></span></div>
                            <div class="audit-body">
                                <div class="audit-top">
                                    <span class="audit-action-tag">{{ $event->action }}</span>
                                    <span class="audit-time-tag">{{ $event->created_at?->diffForHumans() }}</span>
                                </div>
                                <div class="audit-details">
                                    <span>Initiator: <strong>{{ $event->user?->name ?? 'System Daemon' }}</strong></span>
                                    @if ($event->target_type)
                                        <span>· Subject: {{ class_basename($event->target_type) }} #{{ $event->target_id }}</span>
                                    @endif
                                    @if ($event->ip_address)
                                        <span>· IP: {{ $event->ip_address }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state-panel"><p>No audit events recorded in this cycle.</p></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
