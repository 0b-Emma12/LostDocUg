@extends('layouts.app')

@section('title', 'My reports | LostDocUg')

@section('content')
<main class="ul-account-page" x-data="reportDashboard({{ Illuminate\Support\Js::from($reports) }})">
    <div class="wrap">
        <div class="ul-account-head-row">
            <div>
                <p class="ul-account-eyebrow"><span class="ul-eyebrow-dot"></span> Private account</p>
                <h1 class="ul-account-title">My reports</h1>
                <p class="ul-account-sub">Your reports and possible matches are private to your account.</p>
            </div>
            <a class="button button-primary" href="{{ route('reports.create', ['kind' => 'lost']) }}">New report <span aria-hidden="true">&#8594;</span></a>
        </div>

        @if (session('status') === 'email-verified')
            <p class="ul-inline-alert success" role="status">Your email is verified. You can now submit private reports.</p>
        @endif

        <div class="ul-filter-tabs" role="group" aria-label="Filter reports">
            <button type="button" @click="filter = 'all'" :aria-pressed="filter === 'all'">All</button>
            <button type="button" @click="filter = 'lost'" :aria-pressed="filter === 'lost'">Lost</button>
            <button type="button" @click="filter = 'found'" :aria-pressed="filter === 'found'">Found</button>
        </div>

        <section class="ul-box-card mb-24" aria-labelledby="possible-matches-heading">
            <h2 class="ul-box-title" id="possible-matches-heading">Possible matches</h2>
            <p class="ul-box-desc">Possible matches are based on private report details. A suggestion is not proof of ownership; an authorized reviewer must verify any claim.</p>
            @forelse ($matches as $match)
                <a class="ul-report-row" href="{{ $match['url'] }}">
                    <div class="ul-report-row-main">
                        <strong>{{ $match['documentType'] }}</strong>
                        <span class="ul-report-ref">Private reference: {{ $match['reference'] }}</span>
                        <span class="ul-report-sub">
                            {{ $match['district'] ?: 'District not specified' }} · Found around {{ $match['incidentDate'] }}
                        </span>
                    </div>
                    <span class="ul-status-chip" data-status="under_review">
                        {{ $match['claimSubmitted'] ? 'Claim submitted' : 'Review suggestion' }}
                    </span>
                </a>
            @empty
                <p class="ul-form-hint">No possible matches are available yet. We check reports with matching document categories and exact names when new reports are submitted.</p>
            @endforelse
        </section>

        <div class="ul-report-list" x-show="filteredReports.length">
            <template x-for="report in filteredReports" :key="report.id">
                <a class="ul-report-row" :href="`/reports/${report.id}`">
                    <div class="ul-report-row-main">
                        <span class="ul-report-ref" x-text="report.reference"></span>
                        <strong x-text="documentLabel(report.documentType)"></strong>
                        <span class="ul-report-sub">
                            <span class="capitalize" x-text="report.kind"></span>
                            <span x-show="report.district" x-text="` · ${report.district}`"></span>
                        </span>
                    </div>
                    <div class="ul-report-row-meta">
                        <span class="ul-status-chip" :data-status="report.status" x-text="statusLabel(report.status)"></span>
                        <span x-text="formatDate(report.createdAt)"></span>
                        <span x-text="`${report.images} images`"></span>
                    </div>
                </a>
            </template>
        </div>

        <div class="ul-empty-state" x-show="filteredReports.length === 0" x-cloak>
            <h2>No reports yet</h2>
            <p>Start a private report when you need help recovering a document.</p>
            <a class="button button-secondary ul-empty-action" href="{{ route('reports.create', ['kind' => 'lost']) }}">Create your first report <span aria-hidden="true">&#8594;</span></a>
        </div>
    </div>
</main>
@endsection
