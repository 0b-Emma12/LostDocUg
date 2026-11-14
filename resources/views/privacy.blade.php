@extends('layouts.app')

@section('title', 'Privacy & Security | LostDocUg')

@section('content')
<main class="ul-homepage">

    {{-- Page Hero (matches directory index header style) --}}
    <div class="ul-dir-header">
        <div class="wrap">
            <div class="ul-breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span class="bc-sep">/</span>
                <span>Privacy &amp; Security</span>
            </div>
            <h1 class="ul-dir-title">Privacy &amp; Security</h1>
            <p class="ul-dir-subtitle">
                LostDocUg is built privacy-first. Reports and uploaded images remain private to the account that created them and are never treated as proof of ownership or authenticity.
            </p>
        </div>
    </div>

    {{-- Three Principle Cards (same ul-step-card grid as homepage How It Works) --}}
    <section class="ul-how-it-works-section ul-how-it-works-section--padded">
        <div class="ul-section-header center">
            <span class="ul-section-tag">OUR COMMITMENTS</span>
            <h2 class="ul-section-title">Privacy Principles</h2>
            <p class="ul-section-desc">We follow strict civil identification security procedures to protect every citizen's personal data.</p>
        </div>

        <div class="ul-steps-grid">
            <div class="ul-step-card">
                <div class="step-icon-box"><x-icon name="lock" /></div>
                <h3>Private by Default</h3>
                <p>Access is limited to the reporting account and explicit review workflows. There is no public gallery of identity documents or personal details.</p>
            </div>

            <div class="ul-step-card">
                <div class="step-icon-box"><x-icon name="document" /></div>
                <h3>Minimal Data Only</h3>
                <p>Share approximate dates, districts, and broad locations. Avoid identity numbers, passport data, voter details, and other personal identifiers.</p>
            </div>

            <div class="ul-step-card">
                <div class="step-icon-box"><x-icon name="shield" /></div>
                <h3>Human Review Required</h3>
                <p>Any possible match is only an investigation lead, not proof of ownership, legal validity, or document authenticity.</p>
            </div>
        </div>
    </section>

    {{-- Detailed Policy Sections --}}
    <section class="ul-privacy-detail-section">
        <div class="wrap">
            <div class="ul-privacy-detail-grid">

                <div class="ul-privacy-card">
                    <div class="ul-privacy-card-tag">What we ask for</div>
                    <h2 class="ul-privacy-card-title">Information in Reports</h2>
                    <p class="ul-privacy-card-body">Only provide the details needed to help a safe recovery check. Keep reports general and non-identifying whenever possible: an approximate date, issue district, and broad location. Optional name and date-of-birth reading happens in your browser; details you review are encrypted with your private report, are not saved to drafts, and are never shown in public results. Private names and dates are visible only to you and authorized reviewers. Institution name is required for academic certificates and student IDs; certificate type is required for academic certificates. Completion year and student-ID academic year are optional. These details are encrypted in your report and any saved draft and may inform private review suggestions. Do not add identity, passport, student, or voter numbers; grades; land-title, plot, or block references; licence numbers; polling-station details; bank account information; or other sensitive identifiers printed on documents.</p>
                </div>

                <div class="ul-privacy-card">
                    <div class="ul-privacy-card-tag">Protected handling</div>
                    <h2 class="ul-privacy-card-title">Images &amp; Private Storage</h2>
                    <p class="ul-privacy-card-body">Uploaded images are stored privately and are intended only for restricted review. They are re-encoded to remove embedded metadata before handling, and access is restricted to authorized users. Do not upload bank cards, CVVs, magnetic stripe images, PINs, or other payment credentials. If you are trying to recover a bank card, contact the issuing bank or follow the official return process.</p>
                </div>

                <div class="ul-privacy-card">
                    <div class="ul-privacy-card-tag">Important limitation</div>
                    <h2 class="ul-privacy-card-title">Matching Is Not Assurance</h2>
                    <p class="ul-privacy-card-body">Potential matches help triage and review, but they do not prove authenticity, ownership, or a legal right. This platform is not a public verification system, is not a government service, and does not replace a formal institutional process or human review.</p>
                </div>

                <div class="ul-privacy-card ul-privacy-card--accent">
                    <div class="ul-privacy-card-tag">Readiness checklist</div>
                    <h2 class="ul-privacy-card-title">Before Public Rollout</h2>
                    <ul class="ul-privacy-checklist">
                        <li>
                            <span class="ul-check-icon"><x-icon name="check" /></span>
                            Complete privacy and retention policy review
                        </li>
                        <li>
                            <span class="ul-check-icon"><x-icon name="check" /></span>
                            Define deletion and access-expiry workflows
                        </li>
                        <li>
                            <span class="ul-check-icon"><x-icon name="check" /></span>
                            Require secure support and escalation processes
                        </li>
                        <li>
                            <span class="ul-check-icon"><x-icon name="check" /></span>
                            Confirm Ugandan legal and institutional review before public access
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </section>

    {{-- CTA Band --}}
    <section class="ul-privacy-cta-band">
        <div class="wrap">
            <div class="ul-privacy-cta-inner">
                <div>
                    <h2 class="ul-privacy-cta-title">Questions about your data?</h2>
                    <p class="ul-privacy-cta-sub">Contact our civil operations team if you have concerns about a report you filed or data you submitted.</p>
                </div>
                <div class="ul-privacy-cta-actions">
                    <a href="{{ route('dashboard') }}" class="ul-btn-search">
                        Check private matches
                    </a>
                    <a href="{{ route('home') }}#how-it-works" class="ul-privacy-cta-link">
                        How It Works →
                    </a>
                </div>
            </div>
        </div>
    </section>

</main>
@endsection
