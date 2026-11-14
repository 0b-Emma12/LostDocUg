@extends('layouts.app')

@section('title', 'Report ' . $report->public_reference . ' | LostDocUg')

@section('content')
<main class="ul-account-page">
    <div class="wrap">
        <a class="ul-account-back" href="{{ route('dashboard') }}"><span aria-hidden="true">&#8592;</span> My reports</a>

        <div class="ul-account-head-row">
            <div>
                <p class="ul-account-eyebrow"><span class="ul-eyebrow-dot"></span> {{ $report->kind->value === 'lost' ? 'Lost document' : 'Found document' }}</p>
                <h1 class="ul-account-title">{{ $report->document_type->name }}</h1>
                <p class="ul-account-sub">Reference <code class="ul-code">{{ $report->public_reference }}</code></p>
            </div>
            <span class="ul-status-chip" data-status="{{ $report->status->value }}">{{ str($report->status->value)->replace('_', ' ')->title() }}</span>
        </div>

        <div class="ul-box-card mb-24">
            <h2 class="ul-box-title">Report details</h2>
            <p class="ul-box-desc">A possible match is a lead, not proof of ownership or authenticity.</p>
            @if ($report->kind->value === 'lost')
                <p class="ul-form-hint">Possible matches for this report appear on your private dashboard when you sign in.</p>
                <a class="button button-secondary" href="{{ route('dashboard') }}">Check possible matches</a>
            @endif
            <dl class="ul-detail-list">
                <div><dt>Date</dt><dd>{{ $report->incident_date->format('j M Y') }}</dd></div>
                <div><dt>District</dt><dd>{{ $report->district ?: 'Not specified' }}</dd></div>
                <div><dt>General location</dt><dd>{{ $report->location_description ?: 'Not specified' }}</dd></div>
                @php($documentMetadata = $report->document_metadata ?? [])
                @if (filled($documentMetadata['institution_name'] ?? null))
                    <div><dt>Institution (private)</dt><dd>{{ $documentMetadata['institution_name'] }}</dd></div>
                @endif
                @if (filled($documentMetadata['certificate_type'] ?? null))
                    <div><dt>Certificate type (private)</dt><dd>{{ str($documentMetadata['certificate_type'])->replace('_', ' ')->title() }}</dd></div>
                @endif
                @if (filled($documentMetadata['completion_year'] ?? null))
                    <div><dt>Completion year (private)</dt><dd>{{ $documentMetadata['completion_year'] }}</dd></div>
                @endif
                @if (filled($documentMetadata['academic_year'] ?? null))
                    <div><dt>Academic year (private)</dt><dd>{{ $documentMetadata['academic_year'] }}</dd></div>
                @endif
                @if (filled($report->holder_name))
                    <div><dt>Holder name (private)</dt><dd>{{ $report->holder_name }}</dd></div>
                @endif
                @if (filled($report->date_of_birth))
                    <div><dt>Date of birth (private)</dt><dd>{{ \Illuminate\Support\Carbon::parse($report->date_of_birth)->format('j M Y') }}</dd></div>
                @endif
                <div><dt>Created</dt><dd>{{ $report->created_at->format('j M Y, H:i') }}</dd></div>
            </dl>
        </div>

        @if ($report->kind->value === 'found' && $report->station_name)
            <section class="ul-box-card mb-24" aria-labelledby="station-dropoff-status">
                <h2 class="ul-box-title" id="station-dropoff-status">Police station drop-off</h2>
                <p class="ul-box-desc">Finder-entered station details. This is a physical drop-off point; contact between the finder and owner is not provided.</p>
                <dl class="ul-detail-list">
                    <div><dt>Station</dt><dd>{{ $report->station_name }}</dd></div>
                    <div><dt>Location</dt><dd>{{ $report->station_address }}</dd></div>
                    <div>
                        <dt>Receipt status</dt>
                        <dd>
                            @if ($report->station_dropoff_status?->value === 'awaiting_confirmation')
                                Awaiting offline station confirmation; not yet marked deposited.
                            @elseif ($report->station_dropoff_status?->value === 'deposited')
                                Deposited after offline station confirmation.
                            @elseif ($report->station_dropoff_status?->value === 'collected')
                                Collection recorded after offline station confirmation.
                            @endif
                        </dd>
                    </div>
                </dl>
                @if ($report->station_dropoff_status?->value !== 'awaiting_confirmation')
                    <a class="button button-secondary" href="https://www.google.com/maps/dir/?api=1&amp;destination={{ rawurlencode($report->station_address) }}" target="_blank" rel="noopener noreferrer">Get directions</a>
                @endif
            </section>
        @endif

        <div class="ul-box-card">
            <div class="ul-section-head">
                <div>
                    <h2>Private images</h2>
                    <p>Only your signed-in account can view these images.</p>
                </div>
                @if ($report->document_type->allowsImages() && $report->isEditable())
                    <form method="POST" action="{{ route('reports.images.store', $report) }}" enctype="multipart/form-data">
                        @csrf
                        <label class="button button-secondary ul-upload-button">Add image<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required onchange="this.form.submit()"></label>
                    </form>
                @endif
            </div>

            @if ($report->images->isEmpty())
                <p class="ul-form-hint">No images attached.</p>
            @else
                <div class="ul-image-grid">
                    @foreach ($report->images as $image)
                        <figure class="ul-image-figure">
                            <img src="{{ route('reports.images.show', [$report, $image]) }}" alt="Private document image" loading="lazy">
                            <figcaption class="ul-image-figcaption">
                                <span class="ul-image-meta">
                                    {{ $image->width }} × {{ $image->height }} px
                                    <span class="ul-image-status">{{ $image->scan_status->value }}</span>
                                </span>
                                @if ($report->isEditable())
                                    <form method="POST" action="{{ route('reports.images.destroy', [$report, $image]) }}" onsubmit="return confirm('Remove this image?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-action" type="submit">Remove</button>
                                    </form>
                                @endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="ul-callout warn mt-24">
            <strong>A possible match is not proof of ownership or authenticity.</strong>
            <p>Recovery actions require the appropriate verification process.</p>
        </div>
    </div>
</main>
@endsection
