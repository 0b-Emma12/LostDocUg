@extends('layouts.admin')

@section('title', 'Match Candidates Queue | LostDocUg Admin')
@section('page_title', 'Match Queue')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-eyebrow">
                <span class="pulse-dot"></span>
                <span>CROSS-REFERENCE VERIFICATION ENGINE</span>
            </div>
            <h1 class="admin-page-title">Match Review Queue</h1>
            <p class="admin-page-desc">Review possible matches based on exact submitted names and document categories. Scores prioritize review and do not prove ownership or authenticity.</p>
        </div>
        <div class="header-action-group">
            <a href="{{ route('admin.dashboard') }}" class="button button-secondary">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    {{-- Filter & KPI Bar --}}
    <div class="admin-kpi-grid admin-kpi-grid-auto mb-24">
        <div class="kpi-card compact">
            <span class="kpi-title">Total Pairs</span>
            <span class="kpi-number">{{ $stats['total'] }}</span>
            <small class="kpi-breakdown">Calculated candidates</small>
        </div>
        <div class="kpi-card compact highlight-amber">
            <span class="kpi-title">Pending Decision</span>
            <span class="kpi-number">{{ $stats['pending'] }}</span>
            <small class="kpi-breakdown">Require reviewer inspection</small>
        </div>
        <div class="kpi-card compact">
            <span class="kpi-title">High Priority (score ≥80)</span>
            <span class="kpi-number">{{ $stats['high_priority'] }}</span>
            <small class="kpi-breakdown">Requires human assessment</small>
        </div>
        <div class="kpi-card compact">
            <span class="kpi-title">Confirmed Matches</span>
            <span class="kpi-number">{{ $stats['confirmed'] }}</span>
            <small class="kpi-breakdown">Reviewer-confirmed pairs</small>
        </div>
        <div class="kpi-card compact">
            <span class="kpi-title">Dismissed</span>
            <span class="kpi-number">{{ $stats['dismissed'] }}</span>
            <small class="kpi-breakdown">False positives rejected</small>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="admin-filter-card">
        <form method="GET" action="{{ route('admin.matches.index') }}" class="filter-form-row">
            <div class="filter-group status-pills-group">
                <a
                    href="{{ route('admin.matches.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}"
                    class="filter-pill {{ request('status', 'all') === 'all' ? 'is-active' : '' }}"
                >
                    All Matches
                </a>
                <a
                    href="{{ route('admin.matches.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}"
                    class="filter-pill {{ request('status') === 'pending' ? 'is-active' : '' }}"
                >
                    Pending Review ({{ $stats['pending'] }})
                </a>
                <a
                    href="{{ route('admin.matches.index', array_merge(request()->except('min_score', 'page'), ['min_score' => 80])) }}"
                    class="filter-pill {{ request('min_score') == 80 ? 'is-active' : '' }}"
                >
                    High Priority (score ≥80)
                </a>
                <a
                    href="{{ route('admin.matches.index', array_merge(request()->except('status', 'page'), ['status' => 'confirmed'])) }}"
                    class="filter-pill {{ request('status') === 'confirmed' ? 'is-active' : '' }}"
                >
                    Confirmed
                </a>
                <a
                    href="{{ route('admin.matches.index', array_merge(request()->except('status', 'page'), ['status' => 'dismissed'])) }}"
                    class="filter-pill {{ request('status') === 'dismissed' ? 'is-active' : '' }}"
                >
                    Dismissed
                </a>
            </div>

            <div class="filter-search-box">
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search reference or district..."
                    class="search-input"
                >
                <button type="submit" class="button button-primary compact">Filter</button>
                @if (request()->hasAny(['q', 'status', 'min_score']))
                    <a href="{{ route('admin.matches.index') }}" class="button button-secondary compact">Reset</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Match Candidates Table --}}
    <div class="admin-main-card">
        @if ($matches->isEmpty())
            <div class="empty-state-panel">
                <div class="empty-icon-wrap">
                    <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3>No Matches Found</h3>
                <p>No match candidates match your current filter parameters. Try clearing the search or toggling filter pills.</p>
                <a href="{{ route('admin.matches.index') }}" class="button button-secondary compact mt-14">
                    Reset All Filters
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Lost Document Profile</th>
                            <th>Found Document Profile</th>
                            <th>Review Priority</th>
                            <th>Field Signals</th>
                            <th>Status</th>
                            <th>Claims</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($matches as $match)
                            <tr>
                                <td>
                                    <span class="mono-badge">#{{ $match->id }}</span>
                                </td>
                                <td>
                                    <div class="doc-summary-cell">
                                        <strong class="ref-link">{{ $match->lostReport?->public_reference ?? 'Report #'.$match->lost_report_id }}</strong>
                                        <span class="doc-sub">
                                            {{ ucwords(str_replace('_', ' ', $match->lostReport?->document_type?->value ?? 'Document')) }}
                                            · Owner: {{ $match->lostReport?->user?->name ?? 'Citizen' }}
                                        </span>
                                        <span class="doc-sub">
                                            Lost: {{ $match->lostReport?->district ?? 'Uganda' }} ({{ $match->lostReport?->incident_date?->format('M d, Y') ?? 'N/A' }})
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="doc-summary-cell">
                                        <strong class="ref-link">{{ $match->foundReport?->public_reference ?? 'Report #'.$match->found_report_id }}</strong>
                                        <span class="doc-sub">
                                            Finder: {{ $match->foundReport?->user?->name ?? 'Citizen' }}
                                        </span>
                                        <span class="doc-sub">
                                            Found in {{ $match->foundReport?->district ?? 'Uganda' }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $scoreTone = $match->score >= 80 ? 'high' : ($match->score >= 60 ? 'mid' : 'low');
                                    @endphp
                                    <div class="score-pill-box {{ $scoreTone }}">
                                        <span class="score-num">{{ $match->score }}</span>
                                        <span class="score-label">{{ $match->score >= 80 ? 'High priority' : ($match->score >= 60 ? 'Review' : 'Low') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="tag-cloud">
                                        @forelse ($match->reason_codes ?? [] as $reason)
                                            <span class="signal-tag">{{ ucwords(str_replace('_', ' ', $reason)) }}</span>
                                        @empty
                                            <span class="signal-tag muted">No signal details</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <span class="status-indicator-badge {{ $match->status }}">
                                        <span class="dot"></span>
                                        <span>{{ ucfirst(str_replace('_', ' ', $match->status)) }}</span>
                                    </span>
                                </td>
                                <td>
                                    @if ($match->claims->isNotEmpty())
                                        <span class="claims-count-pill alert">{{ $match->claims->count() }} active</span>
                                    @else
                                        <span class="claims-count-pill zero">0 claims</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.matches.show', $match) }}" class="button button-primary compact">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($matches->hasPages())
                <div class="pagination-footer">
                    {{ $matches->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
