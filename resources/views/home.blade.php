@extends('layouts.app')

@section('title', 'LostDocUg | Private Document Recovery Uganda')

@section('content')
<main class="ul-homepage">

    {{-- ===== HERO SECTION ===== --}}
    <section class="ul-hero-section">
        <div class="ul-hero-container">
            <div class="ul-hero-badge">
                <span class="pulse-dot"></span>
                <span>PRIVATE LOST &amp; FOUND REPORTS — UGANDA</span>
            </div>
            <h1 class="ul-hero-title">
                Lost a document? <span class="highlight">Check privately.</span>
            </h1>
            <p class="ul-hero-subtitle">
                Submit a private report. If a found report shares the same document category and name,
                a possible match appears in your account — never in a public directory.
            </p>

            {{-- Hero CTA Buttons --}}
            <div class="ul-hero-cta-row">
                <a href="{{ route('reports.create', ['kind' => 'lost']) }}" class="ul-btn-hero lost">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Report a lost document</span>
                </a>
                <a href="{{ route('reports.create', ['kind' => 'found']) }}" class="ul-btn-hero found">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Report a found document</span>
                </a>
            </div>

            {{-- Stats Counter Bar --}}
            <div class="ul-trust-counter-bar">
                <div class="ul-counter-item">
                    <strong>Private</strong>
                    <span>No public gallery</span>
                </div>
                <div class="ul-counter-sep"></div>
                <div class="ul-counter-item">
                    <strong>Encrypted</strong>
                    <span>PII protected</span>
                </div>
                <div class="ul-counter-sep"></div>
                <div class="ul-counter-item">
                    <strong>135+</strong>
                    <span>Districts covered</span>
                </div>
                <div class="ul-counter-sep"></div>
                <div class="ul-counter-item">
                    <strong>Reviewed</strong>
                    <span>Human verification</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== DUAL ACTION CARDS ===== --}}
    <section class="ul-action-banner-section">
        <div class="ul-action-banner-inner">
            <div class="action-card lost-callout">
                <div class="action-card-text">
                    <span class="action-tag lost">LOST A DOCUMENT?</span>
                    <h3>Keep your report private</h3>
                    <p>Your entered name is encrypted and used only to look for an exact private match with a found report of the same category.</p>
                </div>
                <a href="{{ route('reports.create', ['kind' => 'lost']) }}" class="ul-btn-hero lost">
                    <span>Start a lost report</span>
                    <span aria-hidden="true">&#8594;</span>
                </a>
            </div>

            <div class="action-card found-callout">
                <div class="action-card-text">
                    <span class="action-tag found">FOUND SOMEONE'S DOCUMENT?</span>
                    <h3>Submit it for private matching</h3>
                    <p>Report the category and broad location. The document name is kept private and is never shown in a public search.</p>
                </div>
                <a href="{{ route('reports.create', ['kind' => 'found']) }}" class="ul-btn-hero found">
                    <span>Start a found report</span>
                    <span aria-hidden="true">&#8594;</span>
                </a>
            </div>
        </div>
    </section>

    {{-- ===== HOW IT WORKS ===== --}}
    <section class="ul-how-it-works-section" id="how-it-works">
        <div class="ul-section-header center">
            <span class="ul-section-tag">PRIVATE BY DESIGN</span>
            <h2 class="ul-section-title">How document recovery works</h2>
            <p class="ul-section-desc">Found reports are not published as searchable listings. Possible matches are shown only to the account that filed the corresponding lost report.</p>
        </div>

        <div class="ul-steps-grid">
            <div class="ul-step-card">
                <div class="step-num-bubble">01</div>
                <div class="step-icon-box"><x-icon name="pencil" /></div>
                <h3>File a private report</h3>
                <p>Enter the document category, approximate circumstances, and the name you have reviewed. Do not enter document numbers or payment credentials.</p>
            </div>
            <div class="ul-step-card">
                <div class="step-num-bubble">02</div>
                <div class="step-icon-box"><x-icon name="search" /></div>
                <h3>Check possible matches</h3>
                <p>When a report is submitted, the system checks for an exact name and category match. Suggestions remain private in your account.</p>
            </div>
            <div class="ul-step-card">
                <div class="step-num-bubble">03</div>
                <div class="step-icon-box"><x-icon name="scale" /></div>
                <h3>Submit a claim</h3>
                <p>A possible match is only a lead. Submit a claim without posting sensitive numbers or credentials.</p>
            </div>
            <div class="ul-step-card">
                <div class="step-num-bubble">04</div>
                <div class="step-icon-box"><x-icon name="users" /></div>
                <h3>Human review</h3>
                <p>An authorized reviewer must assess a claim before recovery is coordinated. A name match or OCR result does not prove ownership or authenticity.</p>
            </div>
        </div>
    </section>

    {{-- ===== DOCUMENT CATEGORIES FEATURE SECTION ===== --}}
    <section class="ul-features-section">
        <div class="wrap">
            <div class="ul-section-header center">
                <span class="ul-section-tag">SUPPORTED DOCUMENTS</span>
                <h2 class="ul-section-title">Document categories we cover</h2>
                <p class="ul-section-desc">Submit reports for all major Ugandan identity and administrative documents.</p>
            </div>
            <div class="ul-doc-category-grid">
                <div class="ul-doc-cat-card">
                    <div class="ul-doc-cat-icon">🪪</div>
                    <h4>National ID</h4>
                    <p>NIRA-issued identification cards</p>
                </div>
                <div class="ul-doc-cat-card">
                    <div class="ul-doc-cat-icon">📘</div>
                    <h4>Passport</h4>
                    <p>Ugandan travel documents</p>
                </div>
                <div class="ul-doc-cat-card">
                    <div class="ul-doc-cat-icon">🚗</div>
                    <h4>Driving Permit</h4>
                    <p>Vehicle operator licences</p>
                </div>
                <div class="ul-doc-cat-card">
                    <div class="ul-doc-cat-icon">🏠</div>
                    <h4>Land Title</h4>
                    <p>Property ownership documents</p>
                </div>
                <div class="ul-doc-cat-card">
                    <div class="ul-doc-cat-icon">🎓</div>
                    <h4>Academic Certificate</h4>
                    <p>Degrees, diplomas, transcripts</p>
                </div>
                <div class="ul-doc-cat-card">
                    <div class="ul-doc-cat-icon">🗳️</div>
                    <h4>Electoral Document</h4>
                    <p>Voter registration cards</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== FAQ SECTION ===== --}}
    <section class="ul-faq-section" id="faq">
        <div class="ul-section-header center">
            <span class="ul-section-tag">PRIVACY &amp; SAFETY</span>
            <h2 class="ul-section-title">Frequently asked questions</h2>
        </div>

        <div class="ul-faq-accordion" x-data="{ openFaq: 1 }">
            <div class="ul-faq-item" :class="{ 'is-open': openFaq === 1 }">
                <button type="button" class="ul-faq-question" @click="openFaq = (openFaq === 1 ? 0 : 1)">
                    <span>Can other people search for my name or report?</span>
                    <span class="chevron" x-text="openFaq === 1 ? '−' : '+'"></span>
                </button>
                <div class="ul-faq-answer" x-show="openFaq === 1" x-cloak>
                    <p>No public report search or found-document gallery is provided. Your report and any possible matches are available only after signing in to your account.</p>
                </div>
            </div>
            <div class="ul-faq-item" :class="{ 'is-open': openFaq === 2 }">
                <button type="button" class="ul-faq-question" @click="openFaq = (openFaq === 2 ? 0 : 2)">
                    <span>Does a name match mean the document is mine?</span>
                    <span class="chevron" x-text="openFaq === 2 ? '−' : '+'"></span>
                </button>
                <div class="ul-faq-answer" x-show="openFaq === 2" x-cloak>
                    <p>No. A matching name and document category only create a suggestion. OCR may be inaccurate and every claim requires human review.</p>
                </div>
            </div>
            <div class="ul-faq-item" :class="{ 'is-open': openFaq === 3 }">
                <button type="button" class="ul-faq-question" @click="openFaq = (openFaq === 3 ? 0 : 3)">
                    <span>Should I submit an ID number or bank-card details?</span>
                    <span class="chevron" x-text="openFaq === 3 ? '−' : '+'"></span>
                </button>
                <div class="ul-faq-answer" x-show="openFaq === 3" x-cloak>
                    <p>No. Do not submit NINs, passport numbers, card numbers, PINs, CVVs, magnetic-stripe data, or other payment credentials.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== CTA BAND ===== --}}
    <section class="ul-cta-band">
        <div class="wrap">
            <div class="ul-cta-band-inner">
                <div>
                    <h2 class="ul-cta-band-title">Ready to file a private report?</h2>
                    <p class="ul-cta-band-sub">It takes less than 5 minutes. Your data is encrypted and never published.</p>
                </div>
                <div class="ul-cta-band-actions">
                    <a href="{{ route('reports.create', ['kind' => 'lost']) }}" class="button button-primary">Report Lost Document</a>
                    <a href="{{ route('reports.create', ['kind' => 'found']) }}" class="ul-cta-band-link">I found a document →</a>
                </div>
            </div>
        </div>
    </section>

</main>
@endsection
