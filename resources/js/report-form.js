import Alpine from 'alpinejs';
import { createWorker } from 'tesseract.js';
import englishModelUrl from '@tesseract.js-data/eng/4.0.0_best_int/eng.traineddata.gz?url';
import workerScriptUrl from 'tesseract.js/dist/worker.min.js?url';
import coreLstmUrl from 'tesseract.js-core/tesseract-core-lstm.wasm.js?url';
import coreSimdLstmUrl from 'tesseract.js-core/tesseract-core-simd-lstm.wasm.js?url';
import coreRelaxedSimdLstmUrl from 'tesseract.js-core/tesseract-core-relaxedsimd-lstm.wasm.js?url';

const coreAssetUrls = [coreLstmUrl, coreSimdLstmUrl, coreRelaxedSimdLstmUrl];

function getCoreAssetDirectory() {
    const directories = coreAssetUrls.map((assetUrl) => new URL('.', new URL(assetUrl, window.location.href)).href);
    if (!directories.every((directory) => directory === directories[0])) {
        throw new Error('Local OCR core assets are not in the same directory.');
    }

    return directories[0].replace(/\/$/, '');
}

function extractLabeledNameParts(text) {
    const lines = text.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
    const labelPattern = /\b(full\s+names?|holder(?:['’]s)?\s+name|given\s+names?|surname|names?)\b\s*[:\-]?\s*(.*)$/iu;
    const nameParts = { givenName: '', surname: '', fullName: '' };

    for (let index = 0; index < lines.length; index += 1) {
        const match = lines[index].match(labelPattern);
        if (!match) continue;

        const candidate = (match[2] || lines[index + 1] || '').replace(/\s+/g, ' ').trim();
        if (candidate.length > 120 || !/^[\p{L}\p{M}][\p{L}\p{M}\s'’.-]*$/u.test(candidate)) continue;

        const label = match[1].toLowerCase().replace(/\s+/g, ' ');
        if (label.startsWith('given name')) {
            nameParts.givenName = candidate;
        } else if (label === 'surname') {
            nameParts.surname = candidate;
        } else {
            nameParts.fullName = candidate;
        }
    }

    return nameParts;
}

function combineNameParts({ givenName, surname, fullName }) {
    return fullName || [givenName, surname].filter(Boolean).join(' ');
}

function parseDateOfBirth(candidate) {
    const match = candidate.match(/(\d{4})[./-](\d{1,2})[./-](\d{1,2})|(\d{1,2})[./-](\d{1,2})[./-](\d{4})/u);
    if (!match) return '';

    const [, isoYear, isoMonth, isoDay, localDay, localMonth, localYear] = match;
    const year = isoYear || localYear;
    const month = isoMonth || localMonth;
    const day = isoDay || localDay;
    const dateOfBirth = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    const parsedDate = new Date(`${dateOfBirth}T00:00:00Z`);

    if (
        Number.isNaN(parsedDate.getTime())
        || parsedDate.getUTCFullYear() !== Number(year)
        || parsedDate.getUTCMonth() + 1 !== Number(month)
        || parsedDate.getUTCDate() !== Number(day)
        || dateOfBirth > new Date().toISOString().slice(0, 10)
    ) {
        return '';
    }

    return dateOfBirth;
}

function extractLabeledDateOfBirth(text) {
    const lines = text.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
    const labelPattern = /\b(date\s+of\s+birth|birth\s+date|dob)\b\s*[:\-]?\s*(.*)$/iu;

    for (let index = 0; index < lines.length; index += 1) {
        const match = lines[index].match(labelPattern);
        if (!match) continue;

        const candidate = (match[2] || lines[index + 1] || '').trim();
        const dateOfBirth = parseDateOfBirth(candidate);
        if (dateOfBirth) return dateOfBirth;
    }

    return '';
}

async function prepareOcrImage(file, region = null) {
    const bitmap = await createImageBitmap(file);
    if (region && bitmap.width <= bitmap.height) {
        bitmap.close();
        return null;
    }

    const sourceX = region ? bitmap.width * region.x : 0;
    const sourceY = region ? bitmap.height * region.y : 0;
    const sourceWidth = region ? bitmap.width * region.width : bitmap.width;
    const sourceHeight = region ? bitmap.height * region.height : bitmap.height;
    const maximumDimension = region ? 1400 : 2800;
    const scale = Math.min(region?.scale ?? 3, maximumDimension / Math.max(sourceWidth, sourceHeight));
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(sourceWidth * scale));
    canvas.height = Math.max(1, Math.round(sourceHeight * scale));

    const context = canvas.getContext('2d');
    if (!context) {
        bitmap.close();
        throw new Error('Unable to prepare the image for local OCR.');
    }

    context.filter = 'grayscale(1) contrast(1.3)';
    context.drawImage(bitmap, sourceX, sourceY, sourceWidth, sourceHeight, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    if (!blob) {
        throw new Error('Unable to prepare the image for local OCR.');
    }

    return new File([blob], file.name.replace(/\.[^.]+$/u, '.png'), { type: 'image/png' });
}

Alpine.data('reportForm', ({ kind, draftKey }) => ({
    step: 0,
    steps: ['Document', 'Details', 'Image', 'Review'],
    draftKey,
    idempotencyKey: '',
    form: {
        kind,
        document_type: '',
        incident_date: '',
        district: '',
        location_description: '',
        station_name: '',
        station_address: '',
        document_metadata: {
            institution_name: '',
            certificate_type: '',
            completion_year: '',
            academic_year: '',
        },
    },
    files: [],
    holderName: '',
    holderNameSource: '',
    holderSurname: '',
    holderSurnameSource: '',
    holderGivenName: '',
    holderGivenNameSource: '',
    dateOfBirth: '',
    dateOfBirthSource: '',
    ocrFile: null,
    ocrTask: null,
    ocrSequence: 0,
    ocrProgress: 0,
    ocrStatus: '',
    ocrError: '',
    consentGiven: false,
    submitting: false,
    saving: false,
    lastSaved: false,
    uploadError: '',
    submitError: '',
    saveTimer: null,

    init() {
        this.idempotencyKey = this.restoreIdempotencyKey();
        if (this.draftKey) {
            this.loadDraft();
        }
        this.$watch('form', () => this.scheduleSave());
        this.$watch('step', () => this.scheduleSave());
        this.$watch('form.document_type', (documentType) => {
            this.resetOcrState();
            if (!this.supportsDateOfBirth()) {
                this.dateOfBirth = '';
                this.dateOfBirthSource = '';
            }
            if (!['academic_certificate', 'student_id'].includes(documentType)) {
                this.form.document_metadata = {
                    institution_name: '',
                    certificate_type: '',
                    completion_year: '',
                    academic_year: '',
                };
            } else if (documentType === 'student_id') {
                this.form.document_metadata.certificate_type = '';
                this.form.document_metadata.completion_year = '';
            } else if (documentType === 'academic_certificate') {
                this.form.document_metadata.academic_year = '';
            }
            if (documentType === 'bank_card') {
                this.files = [];
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            } else if (this.files[0]) {
                this.startLocalOcr(this.files[0]);
            }
        });
    },

    restoreIdempotencyKey() {
        const storageKey = this.draftKey ? `report:${this.draftKey}` : `report:new:${kind}`;
        const existing = sessionStorage.getItem(storageKey);
        if (existing) return existing;
        const generated = crypto.randomUUID().replaceAll('-', '');
        sessionStorage.setItem(storageKey, generated);
        return generated;
    },

    rememberIdempotencyKey() {
        if (this.draftKey) {
            sessionStorage.setItem(`report:${this.draftKey}`, this.idempotencyKey);
            sessionStorage.removeItem(`report:new:${kind}`);
        }
    },

    scheduleSave() {
        clearTimeout(this.saveTimer);
        this.saveTimer = setTimeout(() => this.saveDraft(), 900);
    },

    async loadDraft() {
        try {
            const response = await fetch(`/reports/drafts/${encodeURIComponent(this.draftKey)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const result = await response.json();
            this.form = { ...this.form, ...result.payload };
            if (!this.form.document_metadata || typeof this.form.document_metadata !== 'object') {
                this.form.document_metadata = {
                    institution_name: '',
                    certificate_type: '',
                    completion_year: '',
                    academic_year: '',
                };
            }
            this.step = Number.isInteger(result.payload.step) ? result.payload.step : 0;
        } catch {
            this.submitError = 'Your saved draft could not be loaded. Please try again.';
        }
    },

    async saveDraft() {
        if (!this.form.kind && !this.form.document_type && !this.form.incident_date && !this.form.district && !this.form.location_description) return;
        this.saving = true;
        const payload = { ...this.form, step: this.step };
        const documentMetadata = this.buildDocumentMetadata();
        if (Object.keys(documentMetadata).length) {
            payload.document_metadata = documentMetadata;
        } else {
            delete payload.document_metadata;
        }

        try {
            const response = await fetch('/reports/drafts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    draft_key: this.draftKey,
                    payload,
                }),
            });
            if (!response.ok) return;
            const result = await response.json();
            this.draftKey = result.draft_key;
            this.rememberIdempotencyKey();
            this.lastSaved = true;
        } catch {
            this.submitError = 'Draft saving is unavailable right now.';
        } finally {
            this.saving = false;
        }
    },

    nextStep() {
        if (this.step === 0 && (!this.form.kind || !this.form.document_type)) {
            this.submitError = 'Choose a report kind and document category to continue.';
            return;
        }
        if (this.step === 1 && !this.form.incident_date) {
            this.submitError = 'Add the approximate incident date to continue.';
            return;
        }
        if (this.step === 1 && this.form.kind === 'found' && (!this.form.station_name.trim() || !this.form.station_address.trim())) {
            this.submitError = 'Enter the police station name and location for the found document.';
            return;
        }
        if (this.step === 1 && ['academic_certificate', 'student_id'].includes(this.form.document_type)) {
            if (!String(this.form.document_metadata.institution_name ?? '').trim()) {
                this.submitError = 'Enter the institution name for this academic document.';
                return;
            }
            if (this.form.document_type === 'academic_certificate' && !this.form.document_metadata.certificate_type) {
                this.submitError = 'Choose a certificate type for this academic certificate.';
                return;
            }
        }
        this.submitError = '';
        this.step = Math.min(this.step + 1, this.steps.length - 1);
    },

    previousStep() {
        this.submitError = '';
        this.step = Math.max(this.step - 1, 0);
    },

    handleFiles(event) {
        this.uploadError = '';
        const selected = Array.from(event.target.files || []);
        if (this.files.length + selected.length > 3) {
            this.uploadError = 'Choose no more than three images.';
            event.target.value = '';
            return;
        }
        for (const file of selected) {
            if (file.size > 5 * 1024 * 1024) {
                this.uploadError = `${file.name} exceeds the 5 MB limit.`;
                event.target.value = '';
                return;
            }
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                this.uploadError = `${file.name} is not a supported image format.`;
                event.target.value = '';
                return;
            }
        }
        this.files = [...this.files, ...selected];
        event.target.value = '';
        if (this.files[0] && this.ocrFile !== this.files[0]) {
            this.startLocalOcr(this.files[0]);
        }
    },

    removeFile(index) {
        this.files.splice(index, 1);
        this.resetOcrState();
        if (this.files[0]) {
            this.startLocalOcr(this.files[0]);
        }
    },

    resetOcrState() {
        this.ocrSequence += 1;
        this.ocrFile = null;
        this.ocrProgress = 0;
        this.ocrStatus = '';
        this.ocrError = '';
        if (this.holderNameSource === 'ocr') {
            this.holderName = '';
            this.holderNameSource = '';
        }
        if (this.holderSurnameSource === 'ocr') {
            this.holderSurname = '';
            this.holderSurnameSource = '';
        }
        if (this.holderGivenNameSource === 'ocr') {
            this.holderGivenName = '';
            this.holderGivenNameSource = '';
        }
        if (this.dateOfBirthSource === 'ocr') {
            this.dateOfBirth = '';
            this.dateOfBirthSource = '';
        }
    },

    markHolderNameEdited() {
        this.holderNameSource = this.holderName.trim() ? 'manual' : '';
        this.ocrError = '';
    },

    markHolderSurnameEdited() {
        this.holderSurnameSource = this.holderSurname.trim() ? 'manual' : '';
        this.ocrError = '';
    },

    markHolderGivenNameEdited() {
        this.holderGivenNameSource = this.holderGivenName.trim() ? 'manual' : '';
        this.ocrError = '';
    },

    markDateOfBirthEdited() {
        this.dateOfBirthSource = this.dateOfBirth ? 'manual' : '';
        this.ocrError = '';
    },

    startLocalOcr(file) {
        const sequence = ++this.ocrSequence;
        this.ocrFile = file;
        this.ocrProgress = 0;
        this.ocrError = '';
        this.ocrStatus = this.supportsDateOfBirth()
            ? 'Preparing private, on-device name and date reading…'
            : 'Preparing private, on-device name reading…';

        const previousTask = this.ocrTask;
        this.ocrTask = (async () => {
            if (previousTask) {
                await previousTask;
            }
            if (sequence !== this.ocrSequence) {
                return;
            }
            await this.readDocumentDetails(file, sequence);
        })();
    },

    async readDocumentDetails(file, sequence) {
        let worker;
        const supportsDateOfBirth = this.supportsDateOfBirth();
        const recognitionPassCount = this.form.document_type === 'nira_id' ? 3 : 1;
        let completedRecognitionPasses = 0;

        try {
            worker = await createWorker('eng', 1, {
                workerPath: workerScriptUrl,
                corePath: getCoreAssetDirectory(),
                langPath: new URL('.', new URL(englishModelUrl, window.location.href)).href.replace(/\/$/, ''),
                gzip: true,
                logger: ({ status, progress }) => {
                    if (sequence !== this.ocrSequence) return;
                    this.ocrStatus = status === 'recognizing text'
                        ? (supportsDateOfBirth ? 'Reading the name and date on your device…' : 'Reading the name on your device…')
                        : (supportsDateOfBirth ? 'Preparing private, on-device name and date reading…' : 'Preparing private, on-device name reading…');
                    if (status === 'recognizing text') {
                        this.ocrProgress = Math.round(((completedRecognitionPasses + progress) / recognitionPassCount) * 100);
                    }
                },
            });

            const preparedImage = await prepareOcrImage(file);
            const result = await worker.recognize(preparedImage);
            completedRecognitionPasses += 1;
            if (sequence !== this.ocrSequence) {
                return;
            }

            let detectedNameParts = extractLabeledNameParts(result.data.text);
            let detectedDateOfBirth = supportsDateOfBirth ? extractLabeledDateOfBirth(result.data.text) : '';

            if (this.form.document_type === 'nira_id') {
                const nameImage = await prepareOcrImage(file, {
                    x: 0.29,
                    y: 0.24,
                    width: 0.30,
                    height: 0.31,
                    scale: 6,
                });
                if (nameImage) {
                    const nameResult = await worker.recognize(nameImage);
                    completedRecognitionPasses += 1;
                    if (sequence !== this.ocrSequence) {
                        return;
                    }
                    const focusedNameParts = extractLabeledNameParts(nameResult.data.text);
                    detectedNameParts = {
                        givenName: focusedNameParts.givenName || detectedNameParts.givenName,
                        surname: focusedNameParts.surname || detectedNameParts.surname,
                        fullName: focusedNameParts.fullName || detectedNameParts.fullName,
                    };
                } else {
                    completedRecognitionPasses += 1;
                }

                const dateOfBirthImage = await prepareOcrImage(file, {
                    x: 0.60,
                    y: 0.43,
                    width: 0.32,
                    height: 0.23,
                    scale: 6,
                });
                if (dateOfBirthImage) {
                    const dateOfBirthResult = await worker.recognize(dateOfBirthImage);
                    completedRecognitionPasses += 1;
                    if (sequence !== this.ocrSequence) {
                        return;
                    }
                    detectedDateOfBirth = extractLabeledDateOfBirth(dateOfBirthResult.data.text) || detectedDateOfBirth;
                } else {
                    completedRecognitionPasses += 1;
                }
            }

            const detectedFields = [];

            if (this.form.document_type === 'nira_id') {
                if (detectedNameParts.surname) {
                    if (this.holderSurnameSource !== 'manual') {
                        this.holderSurname = detectedNameParts.surname;
                        this.holderSurnameSource = 'ocr';
                    }
                    detectedFields.push(this.holderSurnameSource === 'manual' ? 'surname you entered' : 'surname');
                }

                if (detectedNameParts.givenName) {
                    if (this.holderGivenNameSource !== 'manual') {
                        this.holderGivenName = detectedNameParts.givenName;
                        this.holderGivenNameSource = 'ocr';
                    }
                    detectedFields.push(this.holderGivenNameSource === 'manual' ? 'given name(s) you entered' : 'given name(s)');
                }

                if (!detectedNameParts.surname && !detectedNameParts.givenName && detectedNameParts.fullName) {
                    if (this.holderNameSource !== 'manual') {
                        this.holderName = detectedNameParts.fullName;
                        this.holderNameSource = 'ocr';
                    }
                    detectedFields.push(this.holderNameSource === 'manual' ? 'name you entered' : 'name');
                }
            } else {
                const detectedName = combineNameParts(detectedNameParts);
                if (detectedName) {
                    if (this.holderNameSource === 'manual') {
                        detectedFields.push('name you entered');
                    } else {
                        this.holderName = detectedName;
                        this.holderNameSource = 'ocr';
                        detectedFields.push('name');
                    }
                }
            }

            if (supportsDateOfBirth && detectedDateOfBirth) {
                if (this.dateOfBirthSource === 'manual') {
                    detectedFields.push('date of birth you entered');
                } else {
                    this.dateOfBirth = detectedDateOfBirth;
                    this.dateOfBirthSource = 'ocr';
                    detectedFields.push('date of birth');
                }
            }

            this.ocrStatus = detectedFields.length
                ? `Review the ${detectedFields.join(' and ')} below before submitting.`
                : (supportsDateOfBirth
                    ? 'No clearly labelled name or date of birth was found. You can enter them manually.'
                    : 'No clearly labelled name was found. You can enter it manually.');
            this.ocrProgress = 100;
        } catch {
            if (sequence === this.ocrSequence) {
                this.ocrStatus = '';
                this.ocrError = 'On-device reading could not finish. You can enter the details manually.';
            }
        } finally {
            if (worker) {
                try {
                    await worker.terminate();
                } catch {
                    if (sequence === this.ocrSequence) {
                        this.ocrError = 'The on-device OCR worker could not be closed cleanly.';
                    }
                }
            }
        }
    },

    documentTypeLabel() {
        const labels = {
            nira_id: 'National ID',
            passport: 'Passport',
            driving_licence: 'Driving permit',
            land_title: 'Land title',
            academic_certificate: 'Academic certificate',
            student_id: 'Student ID',
            bank_card: 'Bank card',
            voter_document: 'Electoral document',
        };
        return labels[this.form.document_type] || 'Not selected';
    },

    supportsDateOfBirth() {
        return ['nira_id', 'passport'].includes(this.form.document_type);
    },

    buildDocumentMetadata() {
        const allowedFields = this.form.document_type === 'academic_certificate'
            ? ['institution_name', 'certificate_type', 'completion_year']
            : (this.form.document_type === 'student_id' ? ['institution_name', 'academic_year'] : []);

        return Object.fromEntries(allowedFields
            .map((field) => [field, String(this.form.document_metadata?.[field] ?? '').trim()])
            .filter(([, value]) => value));
    },

    certificateTypeLabel() {
        const labels = {
            bachelors: "Bachelor's degree",
            masters: "Master's degree",
            diploma: 'Diploma',
            certificate: 'Certificate',
            transcript: 'Transcript',
        };

        return labels[this.form.document_metadata?.certificate_type] || 'Not provided';
    },

    reviewedHolderName() {
        if (this.form.document_type === 'nira_id') {
            const nameFromParts = [this.holderGivenName.trim(), this.holderSurname.trim()].filter(Boolean).join(' ');
            return nameFromParts || this.holderName.trim();
        }

        return this.holderName.trim();
    },

    async submitReport() {
        this.submitError = '';
        if (!this.consentGiven) {
            this.submitError = 'Consent is required before submitting this report.';
            return;
        }
        this.submitting = true;
        const body = new FormData();
        for (const [key, value] of Object.entries(this.form)) {
            if (key === 'document_metadata') continue;
            body.append(key, value ?? '');
        }
        for (const [key, value] of Object.entries(this.buildDocumentMetadata())) {
            body.append(`document_metadata[${key}]`, value);
        }
        body.append('consent_version', 'lostdocug-privacy-v2');
        body.append('idempotency_key', this.idempotencyKey);
        const holderName = this.reviewedHolderName();
        if (holderName) body.append('holder_name', holderName);
        if (this.dateOfBirth) body.append('date_of_birth', this.dateOfBirth);
        if (this.draftKey) body.append('draft_key', this.draftKey);
        for (const file of this.files) body.append('images[]', file);

        try {
            const response = await fetch('/reports', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body,
            });
            const result = await response.json();
            if (!response.ok) {
                this.submitError = result.message || Object.values(result.errors || {}).flat().join(' ') || 'The report could not be submitted.';
                return;
            }
            if (this.draftKey) sessionStorage.removeItem(`report:${this.draftKey}`);
            sessionStorage.removeItem(`report:new:${kind}`);
            window.location.assign(result.redirect);
        } catch {
            this.submitError = 'Network error. Your report was not confirmed. Please retry safely.';
        } finally {
            this.submitting = false;
        }
    },
}));
