@extends('layouts.app')

@section('title', 'Create a report | LostDocUg')

@section('content')
    <main class="ul-account-page" x-data="reportForm({ kind: @js($kind), draftKey: @js($draftKey) })" x-init="init()">
        <div class="wrap">
            <a class="ul-account-back" href="{{ route('dashboard') }}"><span aria-hidden="true">&#8592;</span> My reports</a>

            <div class="ul-account-head-row">
                <div>
                    <p class="ul-account-eyebrow"><span class="ul-eyebrow-dot"></span> Private report</p>
                    <h1 class="ul-account-title">{{ $kind === 'lost' ? 'Report a lost document.' : 'Report a found document.' }}</h1>
                    <p class="ul-account-sub">Only share the details needed to coordinate a possible recovery. A match is a lead, not proof of ownership.</p>
                </div>
            </div>

            <div class="ul-callout" role="note">
                <strong>Keep identifying numbers private.</strong>
                <p>Do not enter NINs, passport numbers, card credentials, or other numbers printed on the document.</p>
            </div>

            <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="ul-box-card mt-24" @submit.prevent="submitReport">
                @csrf
                <input type="hidden" name="idempotency_key" :value="idempotencyKey">
                <input type="hidden" name="draft_key" :value="draftKey || ''">
                <input type="hidden" name="consent_version" value="lostdocug-privacy-v2">

                <div class="ul-step-track" aria-label="Report steps">
                    <template x-for="(label, index) in steps" :key="label">
                        <div class="ul-step-marker" :class="{ 'is-current': step === index, 'is-complete': step > index }">
                            <span x-text="index + 1"></span>
                            <small x-text="label"></small>
                        </div>
                    </template>
                </div>

                <section x-show="step === 0" x-cloak class="ul-step-section">
                    <h2>Document type</h2>
                    <fieldset class="ul-choice-fieldset">
                        <legend class="ul-choice-legend">Are you reporting a lost or found document?</legend>
                        <div class="ul-choice-grid">
                            <label class="ul-choice-option"><input type="radio" name="kind" value="lost" x-model="form.kind" required><span>I lost a document</span></label>
                            <label class="ul-choice-option"><input type="radio" name="kind" value="found" x-model="form.kind" required><span>I found a document</span></label>
                        </div>
                    </fieldset>
                    <div class="ul-form-row">
                        <label class="ul-form-label" for="document_type">Document category</label>
                        <select class="ul-form-select" id="document_type" name="document_type" x-model="form.document_type" required>
                            <option value="">Choose a category</option><option value="nira_id">National ID</option><option value="passport">Passport</option><option value="driving_licence">Driving permit</option><option value="land_title">Land title</option><option value="academic_certificate">Academic certificate</option><option value="student_id">Student ID</option><option value="bank_card">Bank card</option><option value="voter_document">Electoral document</option>
                        </select>
                    </div>
                    <div class="ul-callout warn" x-show="form.document_type === 'bank_card'" x-cloak>
                        <strong>Bank cards need a different route.</strong>
                        <p>Do not upload an image or share any card number, PIN, or security code. Contact the issuing bank or follow its approved return process.</p>
                    </div>
                </section>

                <section x-show="step === 1" x-cloak class="ul-step-section">
                    <h2>When, where, and optional details?</h2>
                    <div class="ul-form-row">
                        <label class="ul-form-label" for="incident_date">Approximate date</label>
                        <input class="ul-form-input" id="incident_date" name="incident_date" type="date" x-model="form.incident_date" max="{{ now()->toDateString() }}" min="2020-01-02" required>
                    </div>
                    <div class="ul-form-row">
                        <label class="ul-form-label" for="district">District <span class="optional">Optional</span></label>
                        <select class="ul-form-select" id="district" name="district" x-model="form.district" autocomplete="address-level2">
                            <option value="">&#x2014; Select a district &#x2014;</option>
                            @foreach(config('locations.districts') as $district)
                                <option value="{{ $district }}">{{ Str::title($district) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ul-form-row">
                        <label class="ul-form-label" for="location_description">General location <span class="optional">Optional</span></label>
                        <textarea class="ul-form-textarea" id="location_description" name="location_description" x-model="form.location_description" rows="3" maxlength="500" placeholder="A broad area only. Avoid exact home or workplace addresses."></textarea>
                        <p class="ul-form-hint" x-text="`${(form.location_description || '').length}/500 characters`"></p>
                    </div>
                    <section class="ul-callout station-dropoff-form" x-show="form.kind === 'found'" x-cloak aria-labelledby="station-dropoff-heading">
                        <strong id="station-dropoff-heading">Police station drop-off point</strong>
                        <p>Enter the station where you have physically handed over the document. Receipt stays unconfirmed until an authorized LostDocUg reviewer verifies it with the station. Do not arrange a meetup or contact the document owner.</p>
                        <div class="ul-form-row">
                            <label class="ul-form-label" for="station_name">Police station name</label>
                            <input class="ul-form-input" id="station_name" type="text" x-model="form.station_name" maxlength="160" autocomplete="off" placeholder="Enter the station name">
                        </div>
                        <div class="ul-form-row">
                            <label class="ul-form-label" for="station_address">Station address or location</label>
                            <textarea class="ul-form-textarea" id="station_address" x-model="form.station_address" rows="2" maxlength="300" autocomplete="street-address" placeholder="Enter the station's location and address"></textarea>
                        </div>
                        <p class="ul-form-hint">Station details are not shown to a possible owner until the receipt is confirmed and their claim is approved. This prototype does not verify station names, provide a nearby station list, or calculate distance.</p>
                    </section>
                    <div class="ul-callout warn" x-show="form.document_type === 'land_title'" x-cloak>
                        <strong>High-value document: human review required.</strong>
                        <p>Keep the location broad. Do not enter title references, plot numbers, or block numbers. A possible match never proves ownership or legal validity.</p>
                    </div>
                    <div class="ul-callout warn" x-show="form.document_type === 'driving_licence'" x-cloak>
                        <strong>Do not submit driving-permit identifiers.</strong>
                        <p>Do not enter a licence number, date of birth, address, or blood group. Only the name and broad location are needed for a private review suggestion.</p>
                    </div>
                    <div class="ul-callout warn" x-show="form.document_type === 'voter_document'" x-cloak>
                        <strong>Keep electoral details private.</strong>
                        <p>Do not enter voter numbers, polling stations, or constituency details.</p>
                    </div>
                    <section class="ul-callout academic-document-fields" x-show="['academic_certificate', 'student_id'].includes(form.document_type)" x-cloak aria-labelledby="academic-details-heading">
                        <strong id="academic-details-heading">Academic document details</strong>
                        <p>These details are encrypted and used only for private review suggestions. They do not prove ownership or authenticity.</p>
                        <div class="ul-form-row">
                            <label class="ul-form-label" for="institution_name">Institution name <span aria-hidden="true">*</span></label>
                            <input class="ul-form-input" id="institution_name" type="text" x-model="form.document_metadata.institution_name" :required="['academic_certificate', 'student_id'].includes(form.document_type)" maxlength="200" autocomplete="organization" placeholder="For example, Makerere University">
                        </div>
                        <div x-show="form.document_type === 'academic_certificate'" x-cloak>
                            <div class="ul-form-row">
                                <label class="ul-form-label" for="certificate_type">Certificate type <span aria-hidden="true">*</span></label>
                                <select class="ul-form-select" id="certificate_type" x-model="form.document_metadata.certificate_type" :required="form.document_type === 'academic_certificate'">
                                    <option value="">Choose a type</option>
                                    <option value="bachelors">Bachelor's degree</option>
                                    <option value="masters">Master's degree</option>
                                    <option value="diploma">Diploma</option>
                                    <option value="certificate">Certificate</option>
                                    <option value="transcript">Transcript</option>
                                </select>
                            </div>
                            <div class="ul-form-row">
                                <label class="ul-form-label" for="completion_year">Year of completion <span class="optional">Optional</span></label>
                                <input class="ul-form-input" id="completion_year" type="number" x-model="form.document_metadata.completion_year" min="1900" max="{{ now()->year }}" step="1" inputmode="numeric">
                            </div>
                        </div>
                        <div class="ul-form-row" x-show="form.document_type === 'student_id'" x-cloak>
                            <label class="ul-form-label" for="academic_year">Academic year <span class="optional">Optional</span></label>
                            <input class="ul-form-input" id="academic_year" type="text" x-model="form.document_metadata.academic_year" maxlength="20" autocomplete="off" placeholder="For example, 2024/2025">
                        </div>
                        <p class="ul-form-hint">Do not enter student numbers or grades. If you upload an image, redact those details first. Metadata and images are private to you and authorized reviewers; these details do not verify a document.</p>
                    </section>
                </section>

                <section x-show="step === 2" x-cloak class="ul-step-section">
                    <h2>Document image <span class="optional">Optional</span></h2>
                    <p class="ul-step-desc">Images are stored privately and converted to remove embedded metadata. Never upload a bank-card image. Avoid uploading if the image exposes details you do not want processed.</p>
                    <div class="ul-callout warn" x-show="form.document_type === 'bank_card'" x-cloak>
                        <strong>Image upload is disabled for bank cards.</strong>
                        <p>Contact the issuing bank or its approved return channel.</p>
                    </div>
                    <template x-if="form.document_type !== 'bank_card'">
                        <div>
                            <label class="ul-upload-field">
                                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple @change="handleFiles($event)" x-ref="fileInput">
                                <span class="ul-upload-glyph" aria-hidden="true">+</span>
                                <strong>Select up to three images</strong>
                                <small>JPEG, PNG, or WebP. Maximum 5 MB per file.</small>
                            </label>
                            <ul class="ul-selected-files" x-show="files.length" x-cloak>
                                <template x-for="(file, index) in files" :key="`${file.name}-${file.size}`">
                                    <li><span x-text="file.name"></span><button type="button" class="text-action" @click="removeFile(index)">Remove</button></li>
                                </template>
                            </ul>
                            <p class="ul-form-error" x-show="uploadError" x-text="uploadError" role="alert"></p>
                            <p class="ul-ocr-note">On-device OCR may suggest a name and, for National ID or passport reports only, a date of birth. Your image and document numbers are not sent to an OCR service. Review all suggestions before submitting; OCR is not proof of ownership.</p>
                            <p class="ul-form-hint" x-show="ocrStatus" x-text="ocrStatus" role="status" aria-live="polite"></p>
                            <progress class="ul-ocr-progress" x-show="ocrStatus && ocrProgress > 0 && ocrProgress < 100" x-cloak max="100" :value="ocrProgress" aria-label="On-device text reading progress"></progress>
                            <p class="ul-form-error" x-show="ocrError" x-text="ocrError" role="alert"></p>
                            <div class="ul-form-row" x-show="form.document_type === 'nira_id'" x-cloak>
                                <label class="ul-form-label" for="holder_surname">Surname <span class="optional">Optional</span></label>
                                <input class="ul-form-input" id="holder_surname" type="text" x-model="holderSurname" @input="markHolderSurnameEdited" maxlength="120" autocomplete="off" placeholder="Review or enter the surname">
                            </div>
                            <div class="ul-form-row" x-show="form.document_type === 'nira_id'" x-cloak>
                                <label class="ul-form-label" for="holder_given_name">Given name(s) <span class="optional">Optional</span></label>
                                <input class="ul-form-input" id="holder_given_name" type="text" x-model="holderGivenName" @input="markHolderGivenNameEdited" maxlength="120" autocomplete="off" placeholder="Review or enter given name(s)">
                                <p class="ul-form-hint">Both names are not saved with your draft. They are combined and encrypted with your private report when submitted.</p>
                            </div>
                            <div class="ul-form-row" x-show="form.document_type !== 'nira_id'" x-cloak>
                                <label class="ul-form-label" for="holder_name">Name printed on the document <span class="optional">Optional</span></label>
                                <input class="ul-form-input" id="holder_name" name="holder_name" type="text" x-model="holderName" @input="markHolderNameEdited" maxlength="120" autocomplete="off" placeholder="Review or enter the name only">
                                <p class="ul-form-hint">This name is not saved with your draft. It is encrypted with your private report when you submit it.</p>
                            </div>
                            <div class="ul-form-row" x-show="supportsDateOfBirth()" x-cloak>
                                <label class="ul-form-label" for="date_of_birth">Date of birth <span class="optional">Optional</span></label>
                                <input class="ul-form-input" id="date_of_birth" name="date_of_birth" type="date" x-model="dateOfBirth" @input="markDateOfBirthEdited" max="{{ now()->toDateString() }}" autocomplete="off">
                                <p class="ul-form-hint">This date is not saved with your draft. It is encrypted with your private report on submission and is visible only to you.</p>
                            </div>
                        </div>
                    </template>
                </section>

                <section x-show="step === 3" x-cloak class="ul-step-section">
                    <h2>Review and submit</h2>
                    <dl class="ul-review-list">
                        <div><dt>Report</dt><dd x-text="form.kind === 'found' ? 'Found document' : 'Lost document'"></dd></div>
                        <div><dt>Category</dt><dd x-text="documentTypeLabel()"></dd></div>
                        <div><dt>Date</dt><dd x-text="form.incident_date || 'Not provided'"></dd></div>
                        <div><dt>District</dt><dd x-text="form.district || 'Not provided'"></dd></div>
                        <template x-if="form.kind === 'found'">
                            <div class="ul-review-station">
                                <div><dt>Police station</dt><dd x-text="form.station_name || 'Not provided'"></dd></div>
                                <div><dt>Station location</dt><dd x-text="form.station_address || 'Not provided'"></dd></div>
                                <p>Station receipt will remain pending until confirmed by an authorized reviewer.</p>
                            </div>
                        </template>
                        <div><dt>Holder name</dt><dd x-text="reviewedHolderName() || 'Not provided'"></dd></div>
                        <template x-if="supportsDateOfBirth()">
                            <div><dt>Date of birth</dt><dd x-text="dateOfBirth || 'Not provided'"></dd></div>
                        </template>
                        <template x-if="['academic_certificate', 'student_id'].includes(form.document_type)">
                            <div><dt>Institution</dt><dd x-text="form.document_metadata.institution_name || 'Not provided'"></dd></div>
                        </template>
                        <template x-if="form.document_type === 'academic_certificate'">
                            <div><dt>Certificate type</dt><dd x-text="certificateTypeLabel()"></dd></div>
                        </template>
                        <template x-if="form.document_type === 'academic_certificate'">
                            <div><dt>Completion year</dt><dd x-text="form.document_metadata.completion_year || 'Not provided'"></dd></div>
                        </template>
                        <template x-if="form.document_type === 'student_id'">
                            <div><dt>Academic year</dt><dd x-text="form.document_metadata.academic_year || 'Not provided'"></dd></div>
                        </template>
                        <div><dt>Images</dt><dd x-text="form.document_type === 'bank_card' ? 'Not accepted for bank cards' : `${files.length} selected`"></dd></div>
                    </dl>
                    <label class="ul-form-check"><input type="checkbox" x-model="consentGiven" required><span>I confirm these details are accurate and agree to the <a href="{{ route('privacy') }}">Privacy Notice</a>. A possible match does not prove ownership or authenticity.</span></label>
                    <p class="ul-form-error" x-show="submitError" x-text="submitError" role="alert"></p>
                </section>

                <div class="ul-form-status" aria-live="polite"><span x-show="saving" x-cloak>Saving encrypted draft...</span><span x-show="lastSaved && !saving" x-cloak>Draft saved</span></div>
                <div class="ul-form-actions">
                    <button type="button" class="button button-secondary" x-show="step > 0" @click="previousStep">Back</button>
                    <span class="ul-form-spacer"></span>
                    <button type="button" class="button button-primary" x-show="step < 3" @click="nextStep">Continue <span aria-hidden="true">&#8594;</span></button>
                    <button type="submit" class="button button-primary" x-show="step === 3" :disabled="submitting || !consentGiven"><span x-text="submitting ? 'Submitting...' : 'Submit report'"></span></button>
                </div>
            </form>
        </div>
    </main>
@endsection
