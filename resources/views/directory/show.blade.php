@extends('layouts.app')

@section('title', 'Possible match | LostDocUg')

@section('content')
    <main class="ul-account-page">
        <div class="wrap">
            <a class="ul-account-back" href="{{ route('dashboard') }}"><span aria-hidden="true">&#8592;</span> My reports &amp; matches</a>

            <div class="ul-account-head-row">
                <div>
                    <p class="ul-account-eyebrow"><span class="ul-eyebrow-dot"></span> Private possible match</p>
                    <h1 class="ul-account-title">{{ $report->category_label }}</h1>
                    <p class="ul-account-sub">Reference <code class="ul-code">{{ $report->public_reference }}</code></p>
                </div>
                <span class="ul-status-chip" data-status="under_review">Needs human review</span>
            </div>

            @if (session('success'))
                <p class="ul-inline-alert success" role="status">{{ session('success') }}</p>
            @endif

            <div class="ul-box-card mb-24">
                <h2 class="ul-box-title">Limited match details</h2>
                <p class="ul-box-desc">This suggestion was generated from a matching document category and exact name. The name and document image are not shown here.</p>
                <dl class="ul-detail-list">
                    <div><dt>Document category</dt><dd>{{ $report->category_label }}</dd></div>
                    <div><dt>Broad district</dt><dd>{{ $report->district ?: 'Not specified' }}</dd></div>
                    <div><dt>Approximate date found</dt><dd>{{ $report->incident_date->format('M Y') }}</dd></div>
                    <div><dt>Private reference</dt><dd>{{ $report->public_reference }}</dd></div>
                </dl>
            </div>

            <div class="ul-callout warn mb-24">
                <strong>A match is only a lead.</strong>
                <p>Names can be shared or misread by OCR. This suggestion does not prove ownership or authenticity. An authorized reviewer must assess any claim before recovery.</p>
            </div>

            @if ($claim?->status === 'approved' && in_array($report->station_dropoff_status?->value, ['deposited', 'collected'], true))
                <section class="ul-box-card mb-24" aria-labelledby="confirmed-station-heading">
                    <h2 class="ul-box-title" id="confirmed-station-heading">Police station collection point</h2>
                    <p class="ul-box-desc">Your claim has been approved and station receipt confirmed. Visit this station and verify your identity with the police officer. Do not arrange a meetup with the finder.</p>
                    <dl class="ul-detail-list">
                        <div><dt>Station</dt><dd>{{ $report->station_name }}</dd></div>
                        <div><dt>Address and location</dt><dd>{{ $report->station_address }}</dd></div>
                        <div>
                            <dt>Collection status</dt>
                            <dd>{{ $report->station_dropoff_status->value === 'collected' ? 'Collection recorded' : 'Ready for collection after in-person identity verification' }}</dd>
                        </div>
                    </dl>
                    <a class="button button-primary" href="https://www.google.com/maps/dir/?api=1&amp;destination={{ rawurlencode($report->station_address) }}" target="_blank" rel="noopener noreferrer">Get directions</a>
                </section>
            @elseif ($report->station_dropoff_status?->value === 'awaiting_confirmation')
                <div class="ul-callout mb-24" role="status">
                    <strong>Police station receipt is not yet confirmed.</strong>
                    <p>Collection details will be shared only after an authorized reviewer confirms station receipt and approves your claim.</p>
                </div>
            @endif

            @if ($claimSubmitted)
                @if ($claim->status === 'approved')
                    <p class="ul-inline-alert success" role="status">Your claim has been approved by an authorized reviewer.</p>
                @elseif ($claim->status === 'rejected')
                    <p class="ul-inline-alert" role="status">Your claim was not approved. Contact the LostDocUg review team through the existing support process.</p>
                @else
                    <p class="ul-inline-alert success" role="status">Your claim is submitted and awaiting human review.</p>
                @endif
            @else
                <div class="ul-box-card">
                    <h2 class="ul-box-title">Submit a private claim</h2>
                    <p class="ul-box-desc">Describe a non-sensitive circumstance that may help an authorized reviewer assess your claim. Do not include document numbers, full dates of birth, PINs, CVVs, or other credentials.</p>
                    <form action="{{ route('directory.claim', $report) }}" method="POST" class="ul-claim-form">
                        @csrf
                        <div class="form-field">
                            <label for="claimant_statement" class="widget-label">Claim statement</label>
                            <textarea
                                id="claimant_statement"
                                name="claimant_statement"
                                rows="5"
                                class="widget-textarea"
                                maxlength="1500"
                                placeholder="Share a brief, non-sensitive description to help the reviewer assess your claim."
                                required
                            >{{ old('claimant_statement') }}</textarea>
                            @error('claimant_statement')
                                <span class="widget-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="button button-primary widget-submit-btn">Send claim for human review</button>
                    </form>
                </div>
            @endif
        </div>
    </main>
@endsection
