<?php

namespace App\Http\Requests;

use App\Enums\DocumentKind;
use App\Enums\ReportKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReportRequest extends FormRequest
{
    /**
     * Normalize the district value to uppercase before validation so that
     * user submissions ('Kampala', 'KAMPALA', 'kampala') all match the
     * uppercase entries in config('locations.districts').
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('district')) {
            $this->merge(['district' => strtoupper(trim($this->input('district')))]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasVerifiedEmail() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(ReportKind::class)],
            'document_type' => ['required', Rule::enum(DocumentKind::class)],
            'incident_date' => ['required', 'date', 'before_or_equal:today', 'after:2020-01-01'],
            'district' => ['nullable', 'string', Rule::in(array_merge([''], config('locations.districts')))],
            'location_description' => ['nullable', 'string', 'max:500'],
            'station_name' => ['exclude_unless:kind,found', 'required', 'string', 'max:160'],
            'station_address' => ['exclude_unless:kind,found', 'required', 'string', 'max:300'],
            'holder_name' => ['nullable', 'string', 'max:120', 'regex:/^[\p{L}\p{M}][\p{L}\p{M}\s\'’.-]*$/u'],
            'date_of_birth' => ['nullable', 'prohibited_unless:document_type,nira_id,passport', 'date_format:Y-m-d', 'before_or_equal:today'],
            'document_metadata' => [
                'nullable',
                'array:institution_name,certificate_type,completion_year,academic_year',
                'prohibited_unless:document_type,academic_certificate,student_id',
            ],
            'document_metadata.institution_name' => [
                'required_if:document_type,academic_certificate,student_id',
                'string',
                'max:200',
            ],
            'document_metadata.certificate_type' => [
                'required_if:document_type,academic_certificate',
                'prohibited_unless:document_type,academic_certificate',
                Rule::in(['bachelors', 'masters', 'diploma', 'certificate', 'transcript']),
            ],
            'document_metadata.completion_year' => [
                'nullable',
                'prohibited_unless:document_type,academic_certificate',
                'integer',
                'digits:4',
                'between:1900,'.now()->year,
            ],
            'document_metadata.academic_year' => [
                'nullable',
                'prohibited_unless:document_type,student_id',
                'string',
                'max:20',
            ],
            'consent_version' => ['required', Rule::in(['lostdocug-privacy-v2'])],
            'idempotency_key' => ['required', 'string', 'size:32', 'regex:/^[A-Fa-f0-9]{32}$/'],
            'draft_key' => ['nullable', 'uuid'],
            'images' => ['sometimes', 'array', 'max:3'],
            'images.*' => ['file', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('document_type') === DocumentKind::BankCard->value && $this->hasFile('images')) {
                $validator->errors()->add('images', 'Images are not accepted for bank-card reports. Contact the issuing bank.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_of_birth.prohibited_unless' => 'Date of birth is collected only for National ID and passport reports.',
            'district.in' => 'Please select a valid Uganda district from the list.',
            'document_metadata.array' => 'Only institution, certificate type, completion year, and academic year may be submitted as academic metadata.',
            'document_metadata.prohibited_unless' => 'Academic details are only accepted for academic certificates and student IDs.',
            'document_metadata.institution_name.required_if' => 'Enter the institution name for this academic document.',
            'document_metadata.certificate_type.required_if' => 'Choose a certificate type for this academic certificate.',
            'document_metadata.certificate_type.prohibited_unless' => 'Certificate type is collected only for academic certificates.',
            'document_metadata.completion_year.prohibited_unless' => 'Completion year is collected only for academic certificates.',
            'document_metadata.academic_year.prohibited_unless' => 'Academic year is collected only for student IDs.',
        ];
    }
}
