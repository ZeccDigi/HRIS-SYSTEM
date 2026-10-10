@php

    $p = $employee->pdsPersonal;
    $f = $employee->pdsFamily;
    $q = $employee->pdsQuestionnaire;
    $gov = $employee->pdsGovId;
    $children = $employee->pdsChildren->values();
    $eligibilities = $employee->pdsEligibilities->values();
    $workExperiences = $employee->pdsWorkExperiences->values();
    $voluntaryWorks = $employee->pdsVoluntaryWorks->values();
    $trainings = $employee->pdsTrainings->values();
    $references = $employee->pdsReferences->values();
    $skills = $employee->pdsOthers->where('type', 'Skill')->values();
    $distinctions = $employee->pdsOthers->where('type', 'Distinction')->values();
    $memberships = $employee->pdsOthers->where('type', 'Membership')->values();

    $educationByLevel = $employee->pdsEducation->keyBy(fn ($row) => strtolower(trim((string) $row->level)));
    $educationRows = collect(['elementary', 'secondary', 'vocational', 'college', 'graduate'])->map(function ($level) use ($employee) {
        $row = $employee->pdsEducation->first(function ($item) use ($level) {
            $key = strtolower(trim((string) $item->level));
            return str_contains($key, $level) || ($level === 'secondary' && str_contains($key, 'high')) || ($level === 'graduate' && (str_contains($key, 'master') || str_contains($key, 'doctor')));
        });

        return [
            'school' => $row?->school_name,
            'course' => $row?->course,
            'from' => $row?->period_from,
            'to' => $row?->period_to,
            'highest' => $row?->highest_level,
            'year' => $row?->year_graduated,
            'honors' => $row?->honors,
        ];
    })->values();

    function pdsText(mixed $value, string $fallback = ''): string
    {
        $value = trim((string) ($value ?? ''));
        return $value !== '' ? $value : $fallback;
    }

    function pdsDate(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    function pdsYesNo(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return $value ? 'YES' : 'NO';
    }

    function pdsChecked(mixed $value, string $expected): string
    {
        $value = strtolower(trim((string) ($value ?? '')));
        $expected = strtolower($expected);

        if ($expected === 'male') {
            return $value === 'male' ? 'X' : '';
        }

        if ($expected === 'female') {
            return $value === 'female' ? 'X' : '';
        }

        return str_contains($value, $expected) ? 'X' : '';
    }

    $signatureUrl = $employee->effective_signature_url ?? ($gov?->signature_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($gov->signature_path) : null);
    $signatureMarkup = $signatureUrl ? '<img src="'.e($signatureUrl).'" alt="PDS signature" class="pds-signature">' : '<span class="signature-placeholder">Signature</span>';
    $photoUrl = $employee->profile_picture_url;

    $pdsFieldMap = [
        'surname' => ['sheet' => 'C1', 'range' => 'D10:N10', 'type' => 'text'],
        'first_name' => ['sheet' => 'C1', 'range' => 'D11:K11', 'type' => 'text'],
        'name_extension' => ['sheet' => 'C1', 'range' => 'M12:N12', 'type' => 'text'],
        'middle_name' => ['sheet' => 'C1', 'range' => 'D12:K12', 'type' => 'text'],
        'date_of_birth' => ['sheet' => 'C1', 'range' => 'D13:F13', 'type' => 'date'],
        'place_of_birth' => ['sheet' => 'C1', 'range' => 'D15:F15', 'type' => 'text'],
        'sex_at_birth' => ['sheet' => 'C1', 'range' => 'D16:F16', 'type' => 'select'],
        'civil_status' => ['sheet' => 'C1', 'range' => 'D17:F18', 'type' => 'select'],
        'citizenship_country' => ['sheet' => 'C1', 'range' => 'J16:N16', 'type' => 'text'],

        'height' => ['sheet' => 'C1', 'range' => 'D22:F23', 'type' => 'number'],
        'weight' => ['sheet' => 'C1', 'range' => 'D24:F24', 'type' => 'number'],
        'blood_type' => ['sheet' => 'C1', 'range' => 'D25:F26', 'type' => 'text'],
        'gsis_id' => ['sheet' => 'C1', 'range' => 'D27:F28', 'type' => 'text'],
        'pagibig_id' => ['sheet' => 'C1', 'range' => 'D29:F30', 'type' => 'text'],
        'philhealth_id' => ['sheet' => 'C1', 'range' => 'D31:F31', 'type' => 'text'],
        'sss_id' => ['sheet' => 'C1', 'range' => 'D32:F32', 'type' => 'text'],
        'tin' => ['sheet' => 'C1', 'range' => 'D33:F33', 'type' => 'text'],
        'agency_employee_number' => ['sheet' => 'C1', 'range' => 'D34:F34', 'type' => 'text'],
        'citizenship' => ['sheet' => 'C1', 'range' => 'J13:N13', 'type' => 'text'],
        'residential_address' => ['sheet' => 'C1', 'range' => 'I17:N24', 'type' => 'compound_address'],
        'permanent_address' => ['sheet' => 'C1', 'range' => 'I25:N31', 'type' => 'compound_address'],
        'telephone_number' => ['sheet' => 'C1', 'range' => 'I32:N32', 'type' => 'text'],
        'mobile_number' => ['sheet' => 'C1', 'range' => 'I33:N33', 'type' => 'text'],
        'email_address' => ['sheet' => 'C1', 'range' => 'I34:N34', 'type' => 'text'],
        'spouse_information' => ['sheet' => 'C1', 'range' => 'D36:H42', 'type' => 'compound'],
        'father_information' => ['sheet' => 'C1', 'range' => 'D43:H45', 'type' => 'compound'],
        'mother_information' => ['sheet' => 'C1', 'range' => 'D46:H49', 'type' => 'compound'],
        'elementary_education' => ['sheet' => 'C1', 'range' => 'D54:N54', 'type' => 'row'],
        'secondary_education' => ['sheet' => 'C1', 'range' => 'D55:N55', 'type' => 'row'],
        'vocational_education' => ['sheet' => 'C1', 'range' => 'D56:N56', 'type' => 'row'],
        'college_education' => ['sheet' => 'C1', 'range' => 'D57:N57', 'type' => 'row'],
        'graduate_studies' => ['sheet' => 'C1', 'range' => 'D58:N58', 'type' => 'row'],
        'eligibility_rating' => ['sheet' => 'C2', 'range' => 'F5:F11', 'type' => 'repeat'],
        'eligibility_exam_date' => ['sheet' => 'C2', 'range' => 'G5:H11', 'type' => 'repeat'],
        'eligibility_exam_place' => ['sheet' => 'C2', 'range' => 'I5:I11', 'type' => 'repeat'],
        'license_number' => ['sheet' => 'C2', 'range' => 'J5:J11', 'type' => 'repeat'],
        'license_valid_until' => ['sheet' => 'C2', 'range' => 'K5:K11', 'type' => 'repeat'],
        'work_to' => ['sheet' => 'C2', 'range' => 'C18:C45', 'type' => 'repeat'],
        'position_title' => ['sheet' => 'C2', 'range' => 'D18:F45', 'type' => 'repeat'],
        'department_agency_company' => ['sheet' => 'C2', 'range' => 'G18:I45', 'type' => 'repeat'],
        'appointment_status' => ['sheet' => 'C2', 'range' => 'J18:J45', 'type' => 'repeat'],
        'government_service' => ['sheet' => 'C2', 'range' => 'K18:K45', 'type' => 'repeat'],
        'voluntary_work_from' => ['sheet' => 'C3', 'range' => 'E6:E12', 'type' => 'repeat'],
        'voluntary_work_to' => ['sheet' => 'C3', 'range' => 'F6:F12', 'type' => 'repeat'],
        'number_of_hours' => ['sheet' => 'C3', 'range' => 'G6:G12', 'type' => 'repeat'],
        'position_nature_of_work' => ['sheet' => 'C3', 'range' => 'H6:K12', 'type' => 'repeat'],
        'training_from' => ['sheet' => 'C3', 'range' => 'E18:E38', 'type' => 'repeat'],
        'training_to' => ['sheet' => 'C3', 'range' => 'F18:F38', 'type' => 'repeat'],
        'training_hours' => ['sheet' => 'C3', 'range' => 'G18:G38', 'type' => 'repeat'],
        'training_type' => ['sheet' => 'C3', 'range' => 'H18:H38', 'type' => 'repeat'],
        'conducted_by' => ['sheet' => 'C3', 'range' => 'I18:K38', 'type' => 'repeat'],
        'special_skills_hobbies' => ['sheet' => 'C3', 'range' => 'B42:B48', 'type' => 'repeat'],
        'distinctions_recognition' => ['sheet' => 'C3', 'range' => 'D42:H48', 'type' => 'repeat'],
        'organization_membership' => ['sheet' => 'C3', 'range' => 'J42:K48', 'type' => 'repeat'],
        'question_34b' => ['sheet' => 'C4', 'range' => 'I8:J8', 'type' => 'yes_no'],
        'question_34_details' => ['sheet' => 'C4', 'range' => 'G10:L10', 'type' => 'text'],
        'question_35a' => ['sheet' => 'C4', 'range' => 'I13:J13', 'type' => 'yes_no'],
        'question_35a_details' => ['sheet' => 'C4', 'range' => 'G14:L14', 'type' => 'text'],
        'question_35b' => ['sheet' => 'C4', 'range' => 'I18:J18', 'type' => 'yes_no'],
        'date_filed' => ['sheet' => 'C4', 'range' => 'H20:I20', 'type' => 'date'],
        'case_status' => ['sheet' => 'C4', 'range' => 'G21:I21', 'type' => 'text'],
        'question_36' => ['sheet' => 'C4', 'range' => 'I23:J23', 'type' => 'yes_no'],
        'question_37' => ['sheet' => 'C4', 'range' => 'I27:J27', 'type' => 'yes_no'],
        'question_38a' => ['sheet' => 'C4', 'range' => 'I31:J31', 'type' => 'yes_no'],
        'question_38b' => ['sheet' => 'C4', 'range' => 'I34:J34', 'type' => 'yes_no'],
        'question_39' => ['sheet' => 'C4', 'range' => 'I37:J37', 'type' => 'yes_no'],
        'immigrant_country' => ['sheet' => 'C4', 'range' => 'G38:M38', 'type' => 'text'],
        'indigenous_group' => ['sheet' => 'C4', 'range' => 'I43:J43', 'type' => 'yes_no'],
        'person_with_disability' => ['sheet' => 'C4', 'range' => 'I45:J45', 'type' => 'yes_no'],
        'pwd_id_number' => ['sheet' => 'C4', 'range' => 'G46:I46', 'type' => 'text'],
        'solo_parent' => ['sheet' => 'C4', 'range' => 'I47:J47', 'type' => 'yes_no'],
        'solo_parent_id_number' => ['sheet' => 'C4', 'range' => 'G48:I48', 'type' => 'text'],
        'government_id_number' => ['sheet' => 'C4', 'range' => 'B62:C63', 'type' => 'text'],
        'id_issuance_date_place' => ['sheet' => 'C4', 'range' => 'B64:C65', 'type' => 'text'],
        'date_accomplished' => ['sheet' => 'C4', 'range' => 'F65:I65', 'type' => 'date'],
        'oath_details' => ['sheet' => 'C4', 'range' => 'A67:M67', 'type' => 'text'],
        'children' => ['sheet' => 'C1', 'range' => 'I37:N48', 'type' => 'repeat'],
        'eligibility_name' => ['sheet' => 'C2', 'range' => 'A5:E11', 'type' => 'repeat'],
        'work_from' => ['sheet' => 'C2', 'range' => 'A18:B45', 'type' => 'repeat'],
        'organization_name_address' => ['sheet' => 'C3', 'range' => 'A6:D12', 'type' => 'repeat'],
        'training_title' => ['sheet' => 'C3', 'range' => 'A18:D38', 'type' => 'repeat'],
        'question_34a' => ['sheet' => 'C4', 'range' => 'I6:J6', 'type' => 'yes_no'],
        'references' => ['sheet' => 'C4', 'range' => 'A52:I54', 'type' => 'repeat'],
        'government_id_type' => ['sheet' => 'C4', 'range' => 'B61:C61', 'type' => 'text'],
        'signature' => ['sheet' => 'C4', 'range' => 'F60:I62', 'type' => 'signature'],
        'photograph' => ['sheet' => 'C4', 'range' => 'K56:L56', 'type' => 'photo'],
        'right_thumbmark' => ['sheet' => 'C4', 'range' => 'K65:L65', 'type' => 'thumbmark'],
    ];

    $photoMarkup = $photoUrl ? '<img src="'.e($photoUrl).'" alt="ID photo" class="pds-photo">' : '<span class="photo-placeholder">PHOTO</span>';
@endphp

<x-app-layout>
<x-slot name="title">{{ __('Personal Data Sheet Preview') }}</x-slot>
<style>
:root { --page-w: 215.9mm; --page-h: 330.2mm; --content-w: 202mm; --ink: #000; }
    * { box-sizing: border-box; }
    .pds-preview-page { margin: 0; background: #f8fafc; color: #000; font-family: Arial, Helvetica, sans-serif; }
    .pds-toolbar { display: flex; gap: 12px; justify-content: space-between; align-items: center; max-width: 900px; margin: 0 auto 1rem; padding: 0; font-family: Manrope, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .pds-toolbar-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 12px; }
    .pds-toolbar button, .pds-toolbar a { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 44px; border-radius: 12px; padding: 0 20px; font-family: inherit; font-size: 13px; font-weight: 800; line-height: 1; text-decoration: none; cursor: pointer; transition: background-color .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease; }
    .pds-toolbar svg { width: 16px; height: 16px; flex: 0 0 auto; }
    .pds-back { border: 1px solid #e5e7eb; background: #fff; color: #4b5563; box-shadow: 0 1px 4px rgba(15, 23, 42, .08); }
    .pds-back:hover { background: #f9fafb; color: #4f46e5; }
    .pds-primary { border: 1px solid #002f8f; background: #0038a8; color: #fff; box-shadow: 0 8px 18px rgba(0, 56, 168, .18); letter-spacing: 0; text-transform: uppercase; }
    .pds-primary:hover { background: #002f8f; }
    .pds-secondary { border: 1px solid #1f2937; background: #1f2937; color: #fff; letter-spacing: 0; text-transform: uppercase; }
    .pds-secondary:hover { background: #111827; }
    .pds-document { max-width: calc(var(--page-w) + 12mm); margin: 0 auto 10mm; padding: 3mm; background: #fff; border: 1px solid #e5edf7; border-radius: 14px; box-shadow: 0 10px 30px rgba(15, 23, 42, .06); }
    .pds-page-controls { display: flex; align-items: center; justify-content: center; gap: 14px; max-width: 900px; margin: 0 auto 1rem; font-family: Manrope, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .pds-page-arrow { display: inline-flex; width: 44px; height: 44px; align-items: center; justify-content: center; border: 1px solid #dbeafe; border-radius: 999px; background: #fff; color: #0038a8; box-shadow: 0 1px 4px rgba(15, 23, 42, .08); transition: background-color .15s ease, color .15s ease, opacity .15s ease; }
    .pds-page-arrow:hover:not(:disabled) { background: #eff6ff; }
    .pds-page-arrow:disabled { opacity: .35; cursor: not-allowed; }
    .pds-page-arrow svg { width: 18px; height: 18px; }
    .pds-page-indicator { min-width: 112px; text-align: center; color: #475569; font-size: 13px; font-weight: 800; }
    .pds-document.is-paged .page { display: none; margin-bottom: 0; }
    .pds-document.is-paged .page.is-active { display: block; }
.page { position: relative; width: var(--page-w); height: var(--page-h); margin: 0 auto 8mm; padding: 5mm 4mm; background: #fff; box-shadow: none; overflow: hidden; page-break-after: always; }
    .page:last-of-type { page-break-after: auto; }
table.pds-grid { width: var(--content-w); margin: 0 auto; border-collapse: collapse; table-layout: fixed; font-size: 5.85pt; line-height: .96; color: #000; transform-origin: top left; border-right: 0.55mm solid #000; }
.page-1 table.pds-grid { margin: 6mm auto 0; }
    .pds-grid tr { height: var(--rh); max-height: var(--rh); }
    .pds-grid td { position: relative; padding: .18mm .45mm; vertical-align: middle; overflow: hidden; height: var(--rh); max-height: var(--rh); white-space: normal; overflow-wrap: anywhere; word-break: break-word; line-height: 1.06; }
    .pds-label { display: block; max-width: 100%; max-height: 100%; padding: 0 .15mm; font-weight: 700; white-space: normal; overflow-wrap: anywhere; word-break: break-word; overflow: hidden; line-height: 1.05; }
    .pds-value { display: block; width: 100%; max-width: 100%; min-height: 0; max-height: calc(var(--rh) - .36mm); padding: 0 .35mm; font-weight: 500; white-space: normal; overflow-wrap: anywhere; word-break: break-word; overflow: hidden; line-height: 1.08; text-align: inherit; }
    .pds-address-value { white-space: nowrap !important; overflow-wrap: normal !important; word-break: normal !important; overflow: hidden !important; line-height: 1 !important; font-size: 5.2pt !important; }
    .page-4 .pds-grid tr,
    .page-4 .pds-grid td,
    .page-4 .pds-label,
    .page-4 .pds-value { max-height: none; }
    .pds-item-number { overflow: visible !important; }
    .pds-item-number .pds-label { display: inline-block; width: auto; max-width: none; padding: 0; white-space: nowrap !important; word-break: normal !important; overflow-wrap: normal !important; overflow: visible !important; line-height: 1; }
    .page-1 .pds-grid tr:nth-child(7) td:first-child .pds-label { display: block; width: 196mm; white-space: nowrap; overflow: visible; }
    /* Uniform print typography: keep the official hierarchy, remove random Excel inline font jumps. */
    .pds-grid td { font-size: 5.76pt !important; }
    .pds-grid .pds-label,
    .pds-grid .pds-value { font-size: inherit !important; }
    .page-4 .pds-grid .pds-label,
    .page-4 .pds-grid .pds-value,
    .page-4 .pds-grid td span { font-size: inherit !important; }
    .page-4 .pds-question-text,
    .page-4 .pds-answer-panel,
    .page-4 .pds-grid td[style*="font-size:7.20pt"] .pds-label,
    .page-4 .pds-grid td[style*="font-size:7.20pt"] .pds-value {
        font-size: 6.45pt !important;
        line-height: 1.16;
    }
    .page-4 .pds-grid td[style*="background:#e6e6e6"][style*="font-size:7.20pt"] .pds-label {
        font-weight: 400;
    }
    .pds-question-text { display: grid; grid-template-columns: 5mm 1fr; column-gap: 1.2mm; line-height: 1.14; }
    .pds-answer-panel { display: grid; grid-template-columns: 1fr; row-gap: 1.4mm; line-height: 1.12; }
    .pds-answer-options { display: flex; align-items: center; gap: 12mm; }
    .pds-answer-option { display: inline-flex; align-items: center; gap: 2mm; white-space: nowrap; }
    .pds-answer-box { display: inline-flex; align-items: center; justify-content: center; width: 3.2mm; height: 3.2mm; border: 0.22mm solid #000; font-weight: 700; line-height: 1; }
    .pds-answer-line { min-height: 4.2mm; border-bottom: 0.22mm solid #000; text-align: center; padding: .35mm 1mm; }
    .pds-other-info-header td { line-height: 1.12; }
    .pds-other-info-header .pds-label { line-height: 1.12; overflow: visible; }
    .pds-grid td[style*="font-size:15.84pt"] { font-size: 15.84pt !important; }
    .pds-grid td[style*="font-size:14.40pt"] { font-size: 14.40pt !important; }
.pds-grid td[style*="background:#808080"],
.pds-grid td[style*="background:#7f7f7f"],
.pds-grid td[style*="background:#666666"] { font-size: 7.2pt !important; }
.pds-grid td[style*="font-style:italic"][style*="Page"] { font-size: 5.04pt !important; }
.pds-label-cell-wide { overflow: visible !important; z-index: 3; }
.pds-label-cell-wide > .pds-label {
    position: absolute;
    left: .35mm;
    top: 50%;
    transform: translateY(-50%);
    display: block;
    width: max-content;
    max-width: none;
    white-space: nowrap !important;
    line-height: 1;
}
.pds-section-cell-wide { overflow: visible !important; z-index: 4; }
.pds-section-cell-wide > .pds-label {
    position: absolute;
    left: .7mm;
    top: 50%;
    transform: translateY(-50%);
    display: block;
    width: max-content;
    max-width: none;
    white-space: nowrap !important;
    line-height: 1;
}
.pds-form-heading {
    position: absolute;
    left: 4.8mm;
    top: 5.1mm;
    z-index: 5;
    font-style: italic;
    font-size: 7.4pt;
    line-height: 1.15;
    white-space: nowrap;
}
.pds-form-heading strong {
    display: block;
    font-size: 8pt;
    font-weight: 700;
}
.pds-check-grid { font-size: 5.2pt !important; }
.pds-box { font-size: 4.4pt !important; }
    .pds-grid select, .pds-grid input, .pds-grid textarea { appearance: none; -webkit-appearance: none; border: 0; background: transparent; box-shadow: none; }
    .pds-value:empty::after { content: ''; }
    .pds-value.yes-no { text-align: center; font-weight: 700; }
    .pds-check-grid { display: grid; gap: .15mm .8mm; align-content: center; height: 100%; padding: 0 .35mm; font-size: 5.2pt; font-weight: 500; overflow: hidden; }
    .pds-check-grid.sex { grid-template-columns: 1fr 1fr; }
    .pds-check-grid.civil { grid-template-columns: 1fr 1fr; gap: .85mm 1.2mm; padding: .65mm .35mm .2mm; align-content: center; }
    .pds-check-option { display: inline-flex; align-items: center; gap: .7mm; white-space: nowrap; line-height: 1; }
    .pds-box { display: inline-flex; align-items: center; justify-content: center; width: 2mm; height: 2mm; border: .22mm solid #000; font-size: 4.4pt; font-weight: 700; line-height: 1; flex: 0 0 auto; }
    .citizenship-options { display: grid; grid-template-columns: 1fr 1fr; align-content: center; justify-items: start; width: 100%; height: 100%; column-gap: 6mm; row-gap: 1.05mm; padding: .2mm 1mm .1mm 4mm; line-height: 1; }
    .citizenship-main-options, .citizenship-dual-options { display: contents; }
    .citizenship-options .pds-check-option { display: inline-flex; align-items: center; gap: 1mm; white-space: nowrap; font-size: 5.76pt; font-weight: 500; }
    .citizenship-options .pds-box { width: 2.2mm; height: 2.2mm; flex: 0 0 2.2mm; background: #fff; font-size: 4.8pt; }
    .pds-number-value { text-align: center; }
    .signature-placeholder, .photo-placeholder { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: #6b7280; font-size: 6pt; }
    .pds-photo { display:block; width:100%; height:100%; object-fit: cover; }
    .pds-signature { display:block; width:100%; height:100%; object-fit: contain; }
    .pds-old-c4 { display: none !important; }
    .c4-copy { padding: 4mm 4mm 5mm; }
    .c4-page { width: var(--content-w); height: auto; margin: 0 auto; font-family: Arial, Helvetica, sans-serif; font-size: 7.2pt; line-height: 1.18; color: #000; }
    .c4-page table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .c4-page td, .c4-page th { border: 0.22mm solid #000; padding: 1mm 1.4mm; vertical-align: top; font-weight: 400; overflow: hidden; }
    .c4-page .c4-outer { height: auto; border: 0.55mm solid #000; }
    .c4-page .c4-outer > tbody > tr > td { border-left: 0; border-right: 0; }
    .c4-page .c4-outer > tbody > tr:first-child > td { border-top: 0; }
    .c4-question { background: #e6e6e6; }
    .c4-qgrid { display: grid; grid-template-columns: 5mm 1fr; column-gap: 1mm; }
    .c4-no { white-space: nowrap; }
    .c4-answer { background: #fff; border-left: 0.22mm solid #000 !important; }
    .c4-answer-inner { display: grid; gap: 1.2mm; align-content: center; height: 100%; }
    .c4-options { display: flex; align-items: center; gap: 14mm; white-space: nowrap; }
    .c4-option { display: inline-flex; align-items: center; gap: 2mm; }
    .c4-box { display: inline-flex; align-items: center; justify-content: center; width: 2.5mm; height: 2.5mm; border: 0.22mm solid #000; font-size: 5.4pt; font-weight: 700; line-height: 1; }
    .c4-line { display: block; min-height: 4.2mm; border-bottom: 0.22mm solid #000; text-align: center; padding: .25mm 1mm 0; font-weight: 500; }
    .c4-small { font-size: 6.1pt; line-height: 1.12; }
    .c4-ref-title { height: 7mm; background: #e6e6e6; font-size: 6.8pt; }
    .c4-ref-title strong { font-size: 6.4pt; }
    .c4-ref th { background: #e6e6e6; text-align: center; vertical-align: middle; font-weight: 400; height: 6mm; }
    .c4-ref td { height: 7.4mm; text-align: center; vertical-align: middle; font-weight: 500; }
    .c4-photo-cell { width: 50mm; border-left: 0.55mm solid #000 !important; text-align: center; vertical-align: top !important; padding-top: 8mm !important; }
    .c4-photo-box { width: 31mm; height: 36mm; margin: 0 auto 1.5mm; border: 0.55mm solid #000; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 6.4pt; line-height: 1.15; }
    .c4-photo-box img { width: 100%; height: 100%; object-fit: cover; }
    .c4-photo-label { color: #9ca3af; font-size: 7pt; }
    .c4-declaration { height: 16mm; background: #e6e6e6; border: 0.22mm solid #000; border-top: 0; font-size: 6.55pt; line-height: 1.08; padding: .8mm 1.2mm; }
    .c4-declaration-grid { display: grid; grid-template-columns: 5mm 1fr; column-gap: 1mm; height: 100%; }
    .c4-declaration-grid p { margin: 0; text-align: justify; word-spacing: normal; letter-spacing: 0; }
    .c4-bottom td { border: 0; padding: 0; }
    .c4-bottom-cell { padding: 4mm 3mm 0 !important; border: 0 !important; }
    .c4-id-box, .c4-sign-box, .c4-thumb-box { border: 0.55mm solid #000; height: 35mm; }
    .c4-id-box table { height: 100%; }
    .c4-id-box table td { height: 7mm; border: 0.22mm solid #000; border-left: 0; border-right: 0; padding: .8mm; vertical-align: middle; }
    .c4-id-box table tr:first-child td { border-top: 0; }
    .c4-id-box table tr:last-child td { border-bottom: 0; }
    .c4-id-heading { height: 11mm !important; background: #e6e6e6; font-size: 5.8pt; line-height: 1.15; }
    .c4-sign-main { height: 23mm; display: flex; align-items: center; justify-content: center; color: #f00; font-size: 6.7pt; }
    .c4-sign-main img { width: 100%; height: 100%; object-fit: contain; }
    .c4-caption { height: 4mm; border-top: 0.22mm solid #000; background: #e6e6e6; text-align: center; font-size: 6.2pt; display: flex; align-items: center; justify-content: center; }
    .c4-date-value { background: #fff; }
    .c4-thumb-box { display: flex; flex-direction: column; justify-content: flex-end; width: 44mm; height: 35mm; margin: 9mm auto 0; border: 0.55mm solid #000; }
    .c4-thumb-area { flex: 1; }
    .c4-oath { height: 30mm; border-top: 0.55mm solid #000; border-bottom: 0 !important; text-align: center; padding-top: 5mm !important; }
    .c4-oath-admin { width: 78mm; height: 20mm; margin: 2.5mm auto 0; border: 0.55mm solid #000; display: flex; flex-direction: column; justify-content: flex-end; }
    .c4-oath-admin .c4-sign-main { flex: 1; height: auto; }
    .c4-footer { display: none; }
    .c4-footer-row { display: none; }
    .c4-page-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        width: calc(var(--content-w) + 0.55mm);
        min-height: 3.09mm;
        margin: 0 auto;
        padding-right: 1.25mm;
        border-right: 0.55mm solid #000;
        text-align: right;
        font-style: italic;
        font-weight: 700;
        font-size: 5.76pt;
        line-height: 1;
        white-space: nowrap;
    }
    .continuation { height: auto; min-height: var(--page-h); }
    .continuation h2 { margin: 0 0 4mm; text-align: center; font-size: 12pt; }
    .continuation table { width: 100%; border-collapse: collapse; font-size: 8pt; }
    .continuation th, .continuation td { border: .22mm solid #000; padding: 1.2mm; vertical-align: top; }
    .continuation th { background: #e5e7eb; text-align: center; }
@media print {
@page { size: 215.9mm 330.2mm; margin: 0; }
html, body { width: 215.9mm; margin: 0; background: #fff; }
        body > .flex,
        body > .flex > .flex-1,
        body main,
        body main > div,
        body main > div > div { display: block !important; width: auto !important; max-width: none !important; height: auto !important; margin: 0 !important; padding: 0 !important; overflow: visible !important; background: transparent !important; border: 0 !important; border-radius: 0 !important; box-shadow: none !important; }
        body aside,
        body header { display: none !important; }
        .pds-toolbar,
        .pds-page-controls { display: none !important; }
        .pds-document { max-width: none; margin: 0; padding: 0; background: transparent; border: 0; border-radius: 0; box-shadow: none; }
        .pds-document.is-paged .page { display: block !important; }
        .pds-document.is-paged .pds-old-c4 { display: none !important; }
        .page { margin: 0; box-shadow: none; page-break-after: always; break-after: page; }
        .page:last-of-type { page-break-after: auto; break-after: auto; }
        .pds-grid tr, .pds-grid td, .continuation tr { break-inside: avoid; page-break-inside: avoid; }
    }
</style>

<div class="pds-preview-page min-h-screen py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="pds-toolbar">
            <a href="{{ route('pds.index') }}" class="pds-back">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                </svg>
                Back
            </a>
            <div class="pds-toolbar-actions">
                <button type="button" onclick="window.print()" class="pds-primary">
                    Print PDF
                </button>
                <a href="{{ route('pds.download') }}" data-no-transition class="pds-secondary">Download PDF</a>
            </div>
        </div>
        <div class="pds-page-controls" data-pds-page-controls>
            <button type="button" class="pds-page-arrow" data-pds-prev-page aria-label="Previous page">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <span class="pds-page-indicator" data-pds-page-indicator>Page 1 of 1</span>
            <button type="button" class="pds-page-arrow" data-pds-next-page aria-label="Next page">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
        <div class="pds-document">

<section class="page page-1" aria-label="PDS C1 A4 page">
<table class="pds-grid">
<colgroup>
<col style="width:3.734mm">
<col style="width:20.614mm">
<col style="width:9.133mm">
<col style="width:28.505mm">
<col style="width:9.543mm">
<col style="width:8.437mm">
<col style="width:13.004mm">
<col style="width:15.221mm">
<col style="width:14.389mm">
<col style="width:11.761mm">
<col style="width:19.788mm">
<col style="width:17.433mm">
<col style="width:14.11mm">
<col style="width:16.327mm">
</colgroup>
<tbody>
<tr style="height:0; --rh:0">
<td colspan="14" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:0;padding:0"></td>













</tr>
<tr style="height:2.897mm; --rh:2.897mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"><span class="pds-label">        </span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.04pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:right;vertical-align:middle;font-weight:700;font-style:italic;font-size:8.64pt"></td>
</tr>
<tr style="height:10.623mm; --rh:10.623mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:top;font-style:italic;font-size:5.04pt;padding-left:1mm;padding-top:.25mm"><span class="pds-label" style="line-height:.95;font-weight:600"><strong>CS Form No. 212</strong><br><span style="font-weight:400">Revised 2025</span></span></td>
<td colspan="6" style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:top;font-size:15.84pt"><span class="pds-label">PERSONAL DATA SHEET</span></td>
<td colspan="4" style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:top;font-size:5.04pt"></td>













</tr>
<tr style="height:5.601mm; --rh:5.601mm">
<td colspan="14" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-weight:700;font-style:italic;font-size:5.76pt"><span class="pds-label">WARNING: Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal case/s against the person concerned.</span></td>


</tr>
<tr style="height:2.897mm; --rh:2.897mm">
<td colspan="14" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:5.76pt"><span class="pds-label">READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA SHEET (PDS) BEFORE ACCOMPLISHING THE PDS FORM.</span></td>


</tr>
<tr style="height:0.773mm; --rh:0.773mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:top;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:top;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:14.40pt"></td>
</tr>
<tr style="height:3.283mm; --rh:3.283mm">
<td colspan="14" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:6.48pt;padding-left:.65mm">
    <span class="pds-label" style="font-weight:600;white-space:nowrap;line-height:1">
        Print legibly if accomplished through own handwriting. Tick appropriate boxes (
        <span style="display:inline-block;width:1.8mm;height:1.8mm;border:0.18mm solid #000;vertical-align:middle;margin:0 0.6mm;"></span>
        ) and use separate sheet if necessary. Indicate N/A if not applicable. DO NOT ABBREVIATE.
    </span>
</td>
</tr>
<tr style="height:0.579mm; --rh:0.579mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:4.249mm; --rh:4.249mm">
<td colspan="14" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">I. PERSONAL INFORMATION</span></td>













</tr>
<tr style="height:5.794mm; --rh:5.794mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"><span class="pds-label">1. </span></td>
<td colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">SURNAME</span></td>

<td colspan="11" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($p?->surname ?? $employee->lastname) }}</span></td>










</tr>
<tr style="height:5.794mm; --rh:5.794mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"><span class="pds-label">2. </span></td>
<td colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">FIRST NAME</span></td>

<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($p?->firstname ?? $employee->firstname) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#EAEAEA;text-align:left;vertical-align:middle;white-space:nowrap;font-size:5.04pt"><span class="pds-label" style="white-space:nowrap;word-break:normal;overflow-wrap:normal">NAME EXTENSION <span style="font-weight:400;font-size:4.5pt">(JR., SR.)</span></span></td>


</tr>
<tr style="height:5.601mm; --rh:5.601mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">MIDDLE NAME</span></td>

<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($p?->middlename ?? $employee->middlename) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:6.181mm; --rh:6.181mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"><span class="pds-label" style="line-height:1.05">3.</span></td>
<td colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label" style="line-height:1.05">DATE OF BIRTH<br><span style="font-size:5.2pt">(dd/mm/yyyy)</span></span></td>

<td rowspan="2" colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#fff;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:7.20pt"><span class="pds-value" style="max-height:none">{{ pdsDate($p?->date_of_birth) }}</span></td>


<td rowspan="2" colspan="3" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt;padding-left:0.8mm"><span class="pds-label">16. CITIZENSHIP</span></td>
<td rowspan="2" colspan="5" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#fff;text-align:left;vertical-align:middle;padding:0;font-size:5.76pt">
@php
    $citizenship = strtolower(trim((string) ($p?->citizenship ?? 'filipino')));
    $dualType = strtolower(trim((string) ($p?->citizenship_type ?? '')));

    $isFilipino = $citizenship === 'filipino';
    $isDual = in_array($citizenship, ['dual', 'dual citizenship', 'dual_citizenship'], true);
    $isByBirth = in_array($dualType, ['by birth', 'birth', 'by_birth'], true);
    $isByNaturalization = in_array($dualType, ['by naturalization', 'naturalization', 'by_naturalization'], true);
@endphp
    <div class="citizenship-options">
        <div class="citizenship-main-options">
            <span class="pds-check-option"><span class="pds-box">{{ $isFilipino ? 'X' : '' }}</span> Filipino</span>
            <span class="pds-check-option"><span class="pds-box">{{ $isDual ? 'X' : '' }}</span> Dual Citizenship</span>
        </div>
        <div class="citizenship-dual-options">
            <span class="pds-check-option"><span class="pds-box">{{ $isByBirth ? 'X' : '' }}</span> by birth</span>
            <span class="pds-check-option"><span class="pds-box">{{ $isByNaturalization ? 'X' : '' }}</span> by naturalization</span>
        </div>
    </div>
</td>




</tr>
<tr style="height:3.09mm; --rh:3.09mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"></td>
</tr>
<tr style="height:6.374mm; --rh:6.374mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"><span class="pds-label">4.</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">PLACE OF BIRTH</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#fff;text-align:center;vertical-align:middle;font-size:7.20pt"><span class="pds-value">{{ pdsText($p?->place_of_birth) }}</span></td>


<td colspan="3" style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#EAEAEA;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label" style="font-weight:400">If holder of dual citizenship,</span></td>


<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:7.20pt"><span class="pds-label" style="font-weight:400">Pls. indicate country:</span></td>


</tr>
<tr style="height:6.374mm; --rh:6.374mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"><span class="pds-label">5.</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">SEX AT BIRTH</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:5.76pt"><div class="pds-check-grid sex" data-field="sex_at_birth"><span class="pds-check-option"><span class="pds-box">{{ pdsChecked($p?->sex, 'male') }}</span> Male</span><span class="pds-check-option"><span class="pds-box">{{ pdsChecked($p?->sex, 'female') }}</span> Female</span></div></td>


<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#EAEAEA;text-align:center;vertical-align:top;font-size:5.76pt"><span class="pds-label" style="font-weight:400">please indicate the details.</span></td>


<td colspan="5" style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:7.20pt"><span class="pds-value" data-field="citizenship_country" style="max-height:6.007mm">{{ pdsText($p?->citizenship_country) }}</span></td>
</tr>
<tr style="height:4.056mm; --rh:4.056mm">
<td rowspan="5" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:top;font-size:5.76pt;padding-top:1mm"><span class="pds-label">6.</span></td>
<td rowspan="5" colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;font-size:5.76pt;padding-top:1mm"><span class="pds-label">CIVIL STATUS</span></td>

<td rowspan="5" colspan="3" style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:5.76pt"><div class="pds-check-grid civil" data-field="civil_status"><span class="pds-check-option"><span class="pds-box">{{ pdsChecked($p?->civil_status, 'single') }}</span> Single</span><span class="pds-check-option"><span class="pds-box">{{ pdsChecked($p?->civil_status, 'married') }}</span> Married</span><span class="pds-check-option"><span class="pds-box">{{ pdsChecked($p?->civil_status, 'widow') }}</span> Widowed</span><span class="pds-check-option"><span class="pds-box">{{ pdsChecked($p?->civil_status, 'separated') }}</span> Separated</span><span class="pds-check-option"><span class="pds-box">{{ pdsChecked($p?->civil_status, 'other') }}</span> Other/s:</span></div></td>


<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">17. RESIDENTIAL ADDRESS</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value pds-address-value">{{ pdsText($p?->res_house_no) }}</span></td>


<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value pds-address-value">{{ pdsText($p?->res_street) }}</span></td>


</tr>
<tr style="height:2.318mm; --rh:2.318mm">






<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">House/Block/Lot No.</span></td>


<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">Street</span></td>


</tr>
<tr style="height:1.352mm; --rh:1.352mm">

<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td rowspan="2" colspan="3" style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value pds-address-value" style="max-height:none">{{ pdsText($p?->res_subdivision) }}</span></td>


<td rowspan="2" colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value pds-address-value" style="max-height:none">{{ pdsText($p?->res_barangay) }}</span></td>


</tr>
<tr style="height:2.511mm; --rh:2.511mm">


<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>






</tr>
<tr style="height:2.318mm; --rh:2.318mm">




<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">Subdivision/Village</span></td>


<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">Barangay</span></td>


</tr>
<tr style="height:4.056mm; --rh:4.056mm">
<td rowspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"><span class="pds-label">7.</span></td>
<td rowspan="2" colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">HEIGHT (cm)</span></td>

<td rowspan="2" colspan="3" style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ \App\Support\PdsFormatter::heightCm($p?->height_m, '') }}</span></td>


<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#fff;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value pds-address-value" style="max-height:none">{{ pdsText($p?->res_city) }}</span></td>


<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#fff;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value pds-address-value" style="max-height:none">{{ pdsText($p?->res_province) }}</span></td>


</tr>
<tr style="height:2.125mm; --rh:2.125mm">






<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">City/Municipality</span></td>


<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">Province</span></td>


</tr>
<tr style="height:5.794mm; --rh:5.794mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"><span class="pds-label">8.</span></td>
<td colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">WEIGHT (kg)</span></td>

<td colspan="3" style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value pds-number-value">{{ pdsText($p?->weight_kg) }}</span></td>


<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">ZIP CODE    </span></td>

<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#fff;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-value pds-address-value" style="max-height:none">{{ pdsText($p?->res_zip_code) }}</span></td>





</tr>
<tr style="height:4.056mm; --rh:4.056mm">
<td rowspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"><span class="pds-label">9.</span></td>
<td rowspan="2" colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">BLOOD TYPE</span></td>

<td rowspan="2" colspan="3" style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($p?->blood_type) }}</span></td>


<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">18. PERMANENT ADDRESS</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-value pds-address-value">{{ pdsText($p?->perm_house_no) }}</span></td>


<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-value pds-address-value">{{ pdsText($p?->perm_street) }}</span></td>


</tr>
<tr style="height:2.318mm; --rh:2.318mm">






<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">House/Block/Lot No.</span></td>


<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">Street</span></td>


</tr>
<tr style="height:4.056mm; --rh:4.056mm">
<td rowspan="2" class="pds-item-number" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt;padding:0"><span class="pds-label">10.</span></td>
<td rowspan="2" colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt;padding-left:0.8mm"><span class="pds-label">UMID ID NO.</span></td>

<td rowspan="2" colspan="3" style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($p?->umid_no ?? $p?->gsis_id_no) }}</span></td>


<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-value pds-address-value">{{ pdsText($p?->perm_subdivision) }}</span></td>


<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-value pds-address-value">{{ pdsText($p?->perm_barangay) }}</span></td>


</tr>
<tr style="height:2.318mm; --rh:2.318mm">






<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">Subdivision/Village</span></td>


<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">Barangay</span></td>


</tr>
<tr style="height:4.056mm; --rh:4.056mm">
<td rowspan="2" class="pds-item-number" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt;padding:0"><span class="pds-label">11.</span></td>
<td rowspan="2" colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">PAG-IBIG ID NO.</span></td>

<td rowspan="2" colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($p?->pagibig_id_no) }}</span></td>


<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value pds-address-value" style="max-height:none">{{ pdsText($p?->perm_city) }}</span></td>
<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value pds-address-value" style="max-height:none">{{ pdsText($p?->perm_province) }}</span></td>
</tr>
<tr style="height:2.511mm; --rh:2.511mm">






<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">City/Municipality</span></td>


<td colspan="3" style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-style:italic;font-size:5.76pt"><span class="pds-label">Province</span></td>


</tr>
<tr style="height:6.374mm; --rh:6.374mm">
<td class="pds-item-number" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt;padding:0"><span class="pds-label">12.</span></td>
<td colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">PHILHEALTH NO.</span></td>
<td colspan="3" style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($p?->philhealth_no) }}</span></td>


<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">ZIP CODE    </span></td>

<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#fff;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value pds-address-value" style="max-height:none">{{ pdsText($p?->perm_zip_code) }}</span></td>


</tr>
<tr style="height:6.374mm; --rh:6.374mm">
<td class="pds-item-number" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt;padding:0"><span class="pds-label">13.</span></td>
<td colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">PhilSys Number (PSN):</span></td>
<td colspan="3" style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($p?->philsys_no) }}</span></td>


<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">19. TELEPHONE NO.</span></td>
<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-value">{{ pdsText($p?->telephone_no) }}</span></td>





</tr>
<tr style="height:6.374mm; --rh:6.374mm">
<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">14. TIN NO.</span></td>


<td colspan="3" style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-size:7.20pt"><span class="pds-value">{{ pdsText($p?->tin_no) }}</span></td>


<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#EAEAEA;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">20. MOBILE NO.</span></td>
<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-value">{{ pdsText($p?->mobile_no ?? $employee->contact_number) }}</span></td>





</tr>
<tr style="height:6.374mm; --rh:6.374mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">15. AGENCY EMPLOYEE NO.</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;background:#EAEAEA;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td colspan="3" style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:center;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value" style="white-space:nowrap">{{ pdsText($p?->agency_employee_no) }}</span></td>


<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">21. E-MAIL ADDRESS <span style="font-weight:400">(if any)</span></span></td>
<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-value">{{ pdsText($p?->email_address ?? $employee->user?->email) }}</span></td>





</tr>
<tr style="height:4.249mm; --rh:4.249mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">II.  FAMILY BACKGROUND</span></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"></td>
</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td class="pds-item-number" style="border-left:0.55mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt;padding:0"><span class="pds-label">22.</span></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">SPOUSE&#x27;S SURNAME</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="5" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->spouse_surname) }}</span></td>




<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">23. NAME of CHILDREN  (Write full name and list all)</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">DATE OF BIRTH (dd/mm/yyyy) </span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">  FIRST NAME</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->spouse_firstname) }}</span></td>


<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;white-space:nowrap;font-size:5.04pt"><span class="pds-label" style="white-space:nowrap;word-break:normal;overflow-wrap:normal">NAME EXTENSION <span style="font-weight:400;font-size:4.5pt">(JR., SR.)</span></span></td>

<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(0)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(0)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">  MIDDLE NAME</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="5" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->spouse_middlename) }}</span></td>




<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(1)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(1)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">OCCUPATION</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="5" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->spouse_occupation) }}</span></td>




<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(2)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(2)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">EMPLOYER/BUSINESS NAME</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="5" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->spouse_employer) }}</span></td>




<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(3)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(3)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">BUSINESS ADDRESS</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="5" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->spouse_business_address) }}</span></td>




<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(4)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(4)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">TELEPHONE NO.</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="5" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->spouse_telephone_no) }}</span></td>




<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(5)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(5)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label"><span class="pds-item-number" style="display:inline-block;padding:0;margin-right:1.2mm">24.</span>FATHER&#x27;S SURNAME</span></td>
<td colspan="5" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->father_surname) }}</span></td>




<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(6)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(6)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">FIRST NAME</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->father_firstname) }}</span></td>


<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;white-space:nowrap;font-size:5.04pt"><span class="pds-label" style="white-space:nowrap;word-break:normal;overflow-wrap:normal">NAME EXTENSION <span style="font-weight:400;font-size:4.5pt">(JR., SR.)</span></span></td>

<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(7)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(7)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">MIDDLE NAME</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="5" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->father_middlename) }}</span></td>




<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(8)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(8)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td class="pds-item-number" style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt;padding:0"><span class="pds-label">25.</span></td>
<td colspan="7" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">MOTHER&#x27;S MAIDEN NAME</span></td>






<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(9)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(9)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">SURNAME</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="5" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#fff;text-align:center;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->mother_maiden_surname) }}</span></td>




<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(10)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(10)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">FIRST NAME</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="5" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#fff;text-align:center;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->mother_firstname) }}</span></td>




<td colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:4.80pt"><span class="pds-value">{{ pdsText($children->get(11)?->fullname) }}</span></td>



<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($children->get(11)?->date_of_birth) }}</span></td>

</tr>
<tr style="height:5.408mm; --rh:5.408mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">MIDDLE NAME</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="5" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($f?->mother_middlename) }}</span></td>




<td colspan="6" style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-style:italic;font-size:5.76pt"><span class="pds-label">(Continue on separate sheet if necessary)</span></td>





</tr>
<tr style="height:4.056mm; --rh:4.056mm">
<td colspan="14" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">III.  EDUCATIONAL BACKGROUND</span></td>
</tr>
<tr style="height:3.67mm; --rh:3.67mm">
<td rowspan="3" class="pds-item-number" style="border-left:0.55mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt;padding:0"><span class="pds-label">26.</span></td>
<td rowspan="3" colspan="2" style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">LEVEL</span></td>

<td rowspan="3" colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">NAME OF SCHOOL                                                                                                                                         (Write in full)</span></td>


<td rowspan="3" colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">BASIC EDUCATION/DEGREE/COURSE                                                             (Write in full)                     </span></td>


<td rowspan="2" colspan="2" style="border-left:0;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.04pt"><span class="pds-label">PERIOD OF ATTENDANCE</span></td>

<td rowspan="3" style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.04pt"><span class="pds-label">HIGHEST LEVEL/                     UNITS EARNED       
(if not graduated)</span></td>
<td rowspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.04pt"><span class="pds-label">YEAR GRADUATED     </span></td>
<td rowspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.04pt"><span class="pds-label">SCHOLARSHIP/ ACADEMIC HONORS RECEIVED</span></td>
</tr>
<tr style="height:5.022mm; --rh:5.022mm">














</tr>
<tr style="height:3.67mm; --rh:3.67mm">








<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">From</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">To</span></td>



</tr>
<tr style="height:7.34mm; --rh:7.34mm">
<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">ELEMENTARY</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.76pt"><span class="pds-value">{{ pdsText($educationRows[0]["school"] ?? null) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.04pt"><span class="pds-value">{{ pdsText($educationRows[0]["course"] ?? null) }}</span></td>


<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($educationRows[0]["from"] ?? null) }}</span></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($educationRows[0]["to"] ?? null) }}</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[0]["highest"] ?? null) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[0]["year"] ?? null) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[0]["honors"] ?? null) }}</span></td>
</tr>
<tr style="height:7.34mm; --rh:7.34mm">
<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">SECONDARY</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.76pt"><span class="pds-value">{{ pdsText($educationRows[1]["school"] ?? null) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.04pt"><span class="pds-value">{{ pdsText($educationRows[1]["course"] ?? null) }}</span></td>


<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($educationRows[1]["from"] ?? null) }}</span></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($educationRows[1]["to"] ?? null) }}</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.04pt"><span class="pds-value">{{ pdsText($educationRows[1]["highest"] ?? null) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[1]["year"] ?? null) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[1]["honors"] ?? null) }}</span></td>
</tr>
<tr style="height:7.34mm; --rh:7.34mm">
<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">VOCATIONAL / TRADE COURSE</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.76pt"><span class="pds-value">{{ pdsText($educationRows[2]["school"] ?? null) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.76pt"><span class="pds-value">{{ pdsText($educationRows[2]["course"] ?? null) }}</span></td>


<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($educationRows[2]["from"] ?? null) }}</span></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($educationRows[2]["to"] ?? null) }}</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[2]["highest"] ?? null) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[2]["year"] ?? null) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[2]["honors"] ?? null) }}</span></td>
</tr>
<tr style="height:7.34mm; --rh:7.34mm">
<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">COLLEGE</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.76pt"><span class="pds-value">{{ pdsText($educationRows[3]["school"] ?? null) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.76pt"><span class="pds-value">{{ pdsText($educationRows[3]["course"] ?? null) }}</span></td>


<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($educationRows[3]["from"] ?? null) }}</span></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($educationRows[3]["to"] ?? null) }}</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[3]["highest"] ?? null) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[3]["year"] ?? null) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[3]["honors"] ?? null) }}</span></td>
</tr>
<tr style="height:7.34mm; --rh:7.34mm">
<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">GRADUATE STUDIES</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.76pt"><span class="pds-value">{{ pdsText($educationRows[4]["school"] ?? null) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.76pt"><span class="pds-value">{{ pdsText($educationRows[4]["course"] ?? null) }}</span></td>


<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($educationRows[4]["from"] ?? null) }}</span></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($educationRows[4]["to"] ?? null) }}</span></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[4]["highest"] ?? null) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[4]["year"] ?? null) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($educationRows[4]["honors"] ?? null) }}</span></td>
</tr>
<tr style="height:3.09mm; --rh:3.09mm">
<td colspan="14" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-weight:700;font-style:italic;font-size:5.76pt"><span class="pds-label">(Continue on separate sheet if necessary)</span></td>













</tr>
<tr style="height:13.326mm; --rh:13.326mm">
<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">SIGNATURE</span></td>


<td colspan="6" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.76pt;color:#FF0000"><span class="pds-value">{!! $signatureMarkup !!}</span></td>





<td colspan="2" style="border-left:0;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">DATE</span></td>

<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value">{{ pdsDate(now()) }}</span></td>


</tr>
<tr style="height:3.09mm; --rh:3.09mm">
<td colspan="14" style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#fff;text-align:right;vertical-align:middle;font-style:italic;font-size:5.04pt;white-space:nowrap"><span class="pds-label" style="white-space:nowrap">CS FORM 212 (Revised 2025), Page 1 of 4</span></td>













</tr>
</tbody></table>
</section>
<section class="page page-2" aria-label="PDS C2 A4 page">
<table class="pds-grid">
<colgroup>
<col style="width:6.03mm">
<col style="width:11.563mm">
<col style="width:17.939mm">
<col style="width:19.444mm">
<col style="width:12.91mm">
<col style="width:23.471mm">
<col style="width:12.406mm">
<col style="width:23.976mm">
<col style="width:32.354mm">
<col style="width:20.784mm">
<col style="width:21.123mm">
</colgroup>
<tbody>
<tr style="height:4.953mm; --rh:4.953mm">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">IV.  CIVIL SERVICE ELIGIBILITY</span></td>










</tr>
<tr style="height:4.128mm; --rh:4.128mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">27.</span></td>
<td rowspan="2" colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">CES/CSEE/CAREER SERVICE/RA 1080 (BOARD/ BAR)/UNDER SPECIAL LAWS/CATEGORY II/ IV ELIGIBILITY and ELIGIBILITIES FOR UNIFORMED PERSONNEL</span></td>



<td rowspan="2" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">RATING
(If Applicable)</span></td>
<td rowspan="2" colspan="2" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">DATE OF EXAMINATION / CONFERMENT</span></td>

<td rowspan="2" style="border-left:0.22mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">PLACE OF EXAMINATION / CONFERMENT</span></td>
<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">LICENSE (if applicable)</span></td>

</tr>
<tr style="height:7.017mm; --rh:7.017mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>








<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">NUMBER</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">Valid Until</span></td>
</tr>
<tr style="height:7.43mm; --rh:7.43mm">
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(0)?->title) }}</span></td>




<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(0)?->rating) }}</span></td>
<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(0)?->date_of_exam) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(0)?->place_of_exam) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(0)?->license_number) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(0)?->license_validity) }}</span></td>
</tr>
<tr style="height:7.43mm; --rh:7.43mm">
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(1)?->title) }}</span></td>




<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(1)?->rating) }}</span></td>
<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(1)?->date_of_exam) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(1)?->place_of_exam) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(1)?->license_number) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(1)?->license_validity) }}</span></td>
</tr>
<tr style="height:7.43mm; --rh:7.43mm">
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(2)?->title) }}</span></td>




<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(2)?->rating) }}</span></td>
<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(2)?->date_of_exam) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(2)?->place_of_exam) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(2)?->license_number) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(2)?->license_validity) }}</span></td>
</tr>
<tr style="height:7.43mm; --rh:7.43mm">
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(3)?->title) }}</span></td>




<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(3)?->rating) }}</span></td>
<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(3)?->date_of_exam) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(3)?->place_of_exam) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(3)?->license_number) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(3)?->license_validity) }}</span></td>
</tr>
<tr style="height:7.43mm; --rh:7.43mm">
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(4)?->title) }}</span></td>




<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(4)?->rating) }}</span></td>
<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(4)?->date_of_exam) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(4)?->place_of_exam) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(4)?->license_number) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(4)?->license_validity) }}</span></td>
</tr>
<tr style="height:7.43mm; --rh:7.43mm">
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(5)?->title) }}</span></td>




<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(5)?->rating) }}</span></td>
<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(5)?->date_of_exam) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(5)?->place_of_exam) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(5)?->license_number) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(5)?->license_validity) }}</span></td>
</tr>
<tr style="height:7.43mm; --rh:7.43mm">
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(6)?->title) }}</span></td>




<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(6)?->rating) }}</span></td>
<td colspan="2" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(6)?->date_of_exam) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(6)?->place_of_exam) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($eligibilities->get(6)?->license_number) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($eligibilities->get(6)?->license_validity) }}</span></td>
</tr>
<tr style="height:3.302mm; --rh:3.302mm">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-weight:700;font-style:italic;font-size:5.76pt"><span class="pds-label">(Continue on separate sheet if necessary)</span></td>










</tr>
<tr style="height:4.953mm; --rh:4.953mm">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">V.  WORK EXPERIENCE </span></td>










</tr>
<tr style="height:3.302mm; --rh:3.302mm">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:5.7pt;white-space:nowrap"><span class="pds-label">(Include private employment. Start from your recent work.) Description of duties should be indicated in the attached Work Experience Sheet.</span></td>
</tr>
<tr style="height:4.953mm; --rh:4.953mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">28.</span></td>
<td rowspan="2" colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">INCLUSIVE DATES (dd/mm/yyy)</span></td>

<td rowspan="3" colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">POSITION TITLE                                                                                                                            (Write in full/Do not abbreviate)</span></td>


<td rowspan="3" colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">DEPARTMENT / AGENCY / OFFICE / COMPANY                                                                                             (Write in full/Do not abbreviate)</span></td>


<td rowspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">STATUS OF APPOINTMENT</span></td>
<td rowspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">GOV&#x27;T SERVICE                                                                                                                                       (Y/ N)</span></td>
</tr>
<tr style="height:4.128mm; --rh:4.128mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>










</tr>
<tr style="height:5.159mm; --rh:5.159mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">From</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">To</span></td>








</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(0)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(0)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(0)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(0)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(0)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[0]) ? ($workExperiences[0]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(1)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(1)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(1)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(1)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(1)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[1]) ? ($workExperiences[1]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(2)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(2)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(2)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(2)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(2)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[2]) ? ($workExperiences[2]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(3)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(3)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(3)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(3)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(3)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[3]) ? ($workExperiences[3]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(4)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(4)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(4)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(4)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(4)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[4]) ? ($workExperiences[4]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(5)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(5)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(5)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(5)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(5)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[5]) ? ($workExperiences[5]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(6)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(6)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(6)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(6)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(6)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[6]) ? ($workExperiences[6]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(7)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(7)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(7)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(7)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(7)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[7]) ? ($workExperiences[7]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(8)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(8)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(8)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(8)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(8)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[8]) ? ($workExperiences[8]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(9)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(9)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(9)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(9)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(9)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[9]) ? ($workExperiences[9]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(10)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(10)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(10)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(10)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(10)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[10]) ? ($workExperiences[10]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(11)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(11)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(11)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(11)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(11)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[11]) ? ($workExperiences[11]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(12)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(12)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(12)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(12)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(12)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[12]) ? ($workExperiences[12]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(13)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(13)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(13)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(13)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(13)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[13]) ? ($workExperiences[13]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(14)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(14)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(14)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(14)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(14)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[14]) ? ($workExperiences[14]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(15)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(15)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(15)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(15)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(15)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[15]) ? ($workExperiences[15]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(16)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(16)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(16)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(16)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(16)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[16]) ? ($workExperiences[16]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.81mm; --rh:6.81mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(17)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(17)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(17)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(17)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(17)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[17]) ? ($workExperiences[17]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(18)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(18)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(18)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(18)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(18)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[18]) ? ($workExperiences[18]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(19)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(19)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(19)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(19)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(19)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[19]) ? ($workExperiences[19]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(20)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(20)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(20)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(20)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(20)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[20]) ? ($workExperiences[20]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(21)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(21)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(21)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(21)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(21)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[21]) ? ($workExperiences[21]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(22)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(22)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(22)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(22)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(22)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[22]) ? ($workExperiences[22]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(23)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(23)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(23)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(23)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(23)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[23]) ? ($workExperiences[23]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(24)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(24)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(24)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(24)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(24)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[24]) ? ($workExperiences[24]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(25)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(25)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(25)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(25)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(25)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[25]) ? ($workExperiences[25]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:6.604mm; --rh:6.604mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(26)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(26)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(26)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(26)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(26)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[26]) ? ($workExperiences[26]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:5.985mm; --rh:5.985mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(27)?->date_from) }}</span></td>

<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($workExperiences->get(27)?->date_to) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(27)?->position_title) }}</span></td>


<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(27)?->company) }}</span></td>


<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($workExperiences->get(27)?->appointment_status) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText(isset($workExperiences[27]) ? ($workExperiences[27]->is_gov_service ? "Y" : "N") : null) }}</span></td>
</tr>
<tr style="height:2.683mm; --rh:2.683mm">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-weight:700;font-style:italic;font-size:5.76pt"><span class="pds-label">(Continue on separate sheet if necessary)</span></td>










</tr>
<tr style="height:13.326mm; --rh:13.326mm">
<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">SIGNATURE</span></td>


<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt;color:#FF0000"><span class="pds-value">{!! $signatureMarkup !!}</span></td>




<td style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">DATE</span></td>
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value">{{ pdsDate(now()) }}</span></td>
</tr>
<tr style="height:2.477mm; --rh:2.477mm">
<td colspan="11" style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#fff;text-align:right;vertical-align:middle;font-style:italic;font-size:5.04pt;white-space:nowrap"><span class="pds-label" style="white-space:nowrap">CS FORM 212 (Revised 2025), Page 2 of 4</span></td>










</tr>
</tbody></table>
</section>
<section class="page page-3" aria-label="PDS C3 A4 page">
<table class="pds-grid">
<colgroup>
<col style="width:5.392mm">
<col style="width:44.19mm">
<col style="width:4.73mm">
<col style="width:29.987mm">
<col style="width:14.858mm">
<col style="width:18.809mm">
<col style="width:18.809mm">
<col style="width:16.305mm">
<col style="width:4.34mm">
<col style="width:6.573mm">
<col style="width:38.007mm">
</colgroup>
<tbody>
<tr style="height:0; --rh:0">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:0;padding:0"></td>










</tr>
<tr style="height:5.556mm; --rh:5.556mm">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATION/S</span></td>










</tr>
<tr style="height:3.704mm; --rh:3.704mm">
<td rowspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">29.</span></td>
<td rowspan="2" colspan="3" style="border-left:0;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">NAME &amp; ADDRESS OF ORGANIZATION                                                                                                     (Write in full)</span></td>


<td rowspan="2" colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">INCLUSIVE DATES                                                                                                                             (dd/mm/yyyy)</span></td>

<td rowspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:4.80pt"><span class="pds-label">NUMBER OF HOURS</span></td>
<td rowspan="3" colspan="4" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">POSITION / NATURE OF WORK</span></td>



</tr>
<tr style="height:2.778mm; --rh:2.778mm">











</tr>
<tr style="height:3.334mm; --rh:3.334mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"></td>


<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">From</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">To</span></td>





</tr>
<tr style="height:6.853mm; --rh:6.853mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(0)?->organization_name) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(0)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(0)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(0)?->number_of_hours) }}</span></td>
<td colspan="4" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(0)?->position) }}</span></td>



</tr>
<tr style="height:6.853mm; --rh:6.853mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(1)?->organization_name) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(1)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(1)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(1)?->number_of_hours) }}</span></td>
<td colspan="4" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(1)?->position) }}</span></td>



</tr>
<tr style="height:6.853mm; --rh:6.853mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(2)?->organization_name) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(2)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(2)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(2)?->number_of_hours) }}</span></td>
<td colspan="4" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(2)?->position) }}</span></td>



</tr>
<tr style="height:6.853mm; --rh:6.853mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(3)?->organization_name) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(3)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(3)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(3)?->number_of_hours) }}</span></td>
<td colspan="4" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(3)?->position) }}</span></td>



</tr>
<tr style="height:6.853mm; --rh:6.853mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(4)?->organization_name) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(4)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(4)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(4)?->number_of_hours) }}</span></td>
<td colspan="4" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(4)?->position) }}</span></td>



</tr>
<tr style="height:6.853mm; --rh:6.853mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(5)?->organization_name) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(5)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(5)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(5)?->number_of_hours) }}</span></td>
<td colspan="4" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(5)?->position) }}</span></td>



</tr>
<tr style="height:6.853mm; --rh:6.853mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(6)?->organization_name) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(6)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsDate($voluntaryWorks->get(6)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(6)?->number_of_hours) }}</span></td>
<td colspan="4" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($voluntaryWorks->get(6)?->position) }}</span></td>



</tr>
<tr style="height:2.778mm; --rh:2.778mm">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-weight:700;font-style:italic;font-size:5.76pt"><span class="pds-label">(Continue on separate sheet if necessary)</span></td>










</tr>
<tr style="height:4.445mm; --rh:4.445mm">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">VII.  LEARNING AND DEVELOPMENT (L&amp;D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED</span></td>










</tr>
<tr style="height:4.445mm; --rh:4.445mm">
<td rowspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">30.</span></td>
<td rowspan="3" colspan="3" style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">TITLE OF LEARNING AND DEVELOPMENT INTERVENTIONS/TRAINING PROGRAMS                                  (Write in full)</span></td>


<td rowspan="2" colspan="2" style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">INCLUSIVE DATES OF ATTENDANCE                                                                                                    (dd/mm/yyyy)</span></td>

<td rowspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">NUMBER OF HOURS</span></td>
<td rowspan="3" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.04pt"><span class="pds-label">Type of L&amp;D
 ( Managerial/ Supervisory/
Technical/etc) </span></td>
<td rowspan="3" colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label"> CONDUCTED/ SPONSORED BY                                                               (Write in full)</span></td>


</tr>
<tr style="height:6.297mm; --rh:6.297mm">











</tr>
<tr style="height:3.334mm; --rh:3.334mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>



<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">From</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">To</span></td>





</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(0)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(0)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(0)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(0)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(0)?->type) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(0)?->conducted_by) }}</span></td>


</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(1)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(1)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(1)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(1)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(1)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(1)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(2)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(2)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(2)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(2)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(2)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(2)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(3)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(3)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(3)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(3)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(3)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(3)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(4)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(4)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(4)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(4)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(4)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(4)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(5)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(5)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(5)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(5)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(5)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(5)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(6)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(6)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(6)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(6)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(6)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(6)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(7)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(7)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(7)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(7)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(7)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(7)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:5.927mm; --rh:5.927mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(8)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(8)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(8)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(8)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(8)?->type) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(8)?->conducted_by) }}</span></td>


</tr>
<tr style="height:5.927mm; --rh:5.927mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(9)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(9)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(9)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(9)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(9)?->type) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(9)?->conducted_by) }}</span></td>


</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(10)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(10)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(10)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(10)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(10)?->type) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(10)?->conducted_by) }}</span></td>


</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(11)?->title) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(11)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(11)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(11)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(11)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(11)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(12)?->title) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(12)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(12)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(12)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(12)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(12)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(13)?->title) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(13)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(13)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(13)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(13)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(13)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(14)?->title) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(14)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(14)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(14)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(14)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(14)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(15)?->title) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(15)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(15)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(15)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(15)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(15)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(16)?->title) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(16)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(16)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(16)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(16)?->type) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(16)?->conducted_by) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"></td>
</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(17)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(17)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(17)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(17)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(17)?->type) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(17)?->conducted_by) }}</span></td>


</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(18)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(18)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(18)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(18)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(18)?->type) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(18)?->conducted_by) }}</span></td>


</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(19)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(19)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(19)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(19)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(19)?->type) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(19)?->conducted_by) }}</span></td>


</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(20)?->title) }}</span></td>



<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(20)?->date_from) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsDate($trainings->get(20)?->date_to) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(20)?->number_of_hours) }}</span></td>
<td style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(20)?->type) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($trainings->get(20)?->conducted_by) }}</span></td>


</tr>
<tr style="height:3.21mm; --rh:3.21mm">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-weight:700;font-style:italic;font-size:5.76pt"><span class="pds-label">(Continue on separate sheet if necessary)</span></td>










</tr>
<tr style="height:5.556mm; --rh:5.556mm">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">VIII.  OTHER INFORMATION</span></td>










</tr>
<tr class="pds-other-info-header" style="height:8.334mm; --rh:8.334mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"><span class="pds-label">31.</span></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">SPECIAL SKILLS and HOBBIES</span></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">32.</span></td>
<td colspan="5" style="border-left:0;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">NON-ACADEMIC DISTINCTIONS / RECOGNITION                                                                                                                                              (Write in full)</span></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">33.</span></td>
<td colspan="2" style="border-left:0;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">MEMBERSHIP IN ASSOCIATION/ORGANIZATION                                                                                         (Write in full)</span></td>

</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>

<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>





<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>


</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>

<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>





<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>


</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>

<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>





<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>


</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>

<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>





<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>


</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>

<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>





<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>


</tr>
<tr style="height:6.112mm; --rh:6.112mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>

<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>





<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>


</tr>
<tr style="height:6.297mm; --rh:6.297mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>

<td colspan="6" style="border-left:0.22mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>





<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>


</tr>
<tr style="height:2.778mm; --rh:2.778mm">
<td colspan="11" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-weight:700;font-style:italic;font-size:5.76pt"><span class="pds-label">(Continue on separate sheet if necessary)</span></td>










</tr>
<tr style="height:13.326mm; --rh:13.326mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">SIGNATURE</span></td>

<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt;color:#FF0000"><span class="pds-value">{!! $signatureMarkup !!}</span></td>



<td colspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-weight:700;font-style:italic;font-size:7.92pt"><span class="pds-label">DATE</span></td>

<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-value">{{ pdsDate(now()) }}</span></td>


</tr>
<tr style="height:2.408mm; --rh:2.408mm">
<td colspan="11" style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#fff;text-align:right;vertical-align:middle;font-style:italic;font-size:5.04pt;white-space:nowrap"><span class="pds-label" style="white-space:nowrap">CS FORM 212 (Revised 2025), Page 3 of 4</span></td>
</tr>
</tbody></table>
</section>
<section class="page page-4 pds-old-c4" aria-label="PDS C4 A4 page">
<table class="pds-grid">
<colgroup>
<col style="width:2.798mm">
<col style="width:21.071mm">
<col style="width:21.071mm">
<col style="width:39.78mm">
<col style="width:4.862mm">
<col style="width:43.762mm">
<col style="width:6.04mm">
<col style="width:11.346mm">
<col style="width:4.862mm">
<col style="width:3.976mm">
<col style="width:12.08mm">
<col style="width:27.111mm">
<col style="width:3.242mm">
</colgroup>
<tbody>
<tr style="height:0; --rh:0">
<td colspan="13" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:0;padding:0"></td>












</tr>
<tr style="height:32mm; --rh:32mm">
<td colspan="6" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;font-size:7.2pt;padding:1.1mm 1.6mm">
    <div style="display:grid;grid-template-columns:5mm 1fr;column-gap:1.4mm;line-height:1.45">
        <span class="pds-label" style="font-weight:400;padding:0">34.</span>
        <div class="pds-label" style="font-weight:400;padding:0;overflow:visible">
            Are you related by consanguinity or affinity to the appointing or recommending authority, or to the<br>
            chief of bureau or office or to the person who has immediate supervision over you in the Office,<br>
            Bureau or Department where you will be appointed,<br>
            a. within the third degree?<br>
            b. within the fourth degree (for Local Government Unit - Career Employees)?
        </div>
    </div>
</td>
<td colspan="7" style="border-left:0;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;font-size:7.2pt;padding:9mm 2mm 1.2mm">
    <div style="display:grid;grid-template-columns:1fr;row-gap:1.8mm;line-height:1.1">
        <div style="display:flex;align-items:center;gap:12mm">
            <span style="display:inline-flex;align-items:center;gap:2.2mm"><span style="display:inline-flex;align-items:center;justify-content:center;width:3.4mm;height:3.4mm;border:0.22mm solid #000;font-weight:700;line-height:1">{{ $q?->q34_a === null || $q?->q34_a === '' ? '' : ($q?->q34_a ? 'X' : '') }}</span>YES</span>
            <span style="display:inline-flex;align-items:center;gap:2.2mm"><span style="display:inline-flex;align-items:center;justify-content:center;width:3.4mm;height:3.4mm;border:0.22mm solid #000;font-weight:700;line-height:1">{{ $q?->q34_a === null || $q?->q34_a === '' ? '' : (!$q?->q34_a ? 'X' : '') }}</span>NO</span>
        </div>
        <div style="display:flex;align-items:center;gap:12mm">
            <span style="display:inline-flex;align-items:center;gap:2.2mm"><span style="display:inline-flex;align-items:center;justify-content:center;width:3.4mm;height:3.4mm;border:0.22mm solid #000;font-weight:700;line-height:1">{{ $q?->q34_b === null || $q?->q34_b === '' ? '' : ($q?->q34_b ? 'X' : '') }}</span>YES</span>
            <span style="display:inline-flex;align-items:center;gap:2.2mm"><span style="display:inline-flex;align-items:center;justify-content:center;width:3.4mm;height:3.4mm;border:0.22mm solid #000;font-weight:700;line-height:1">{{ $q?->q34_b === null || $q?->q34_b === '' ? '' : (!$q?->q34_b ? 'X' : '') }}</span>NO</span>
        </div>
        <div>If YES, give details:</div>
        <div style="min-height:5mm;border-bottom:0.22mm solid #000;text-align:center;padding:.4mm 1mm">{{ pdsText($q?->q34_details) }}</div>
    </div>
</td>
</tr>
<tr style="height:17mm; --rh:17mm">
<td colspan="6" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;padding:1mm 1.6mm">
    <div class="pds-question-text">
        <span class="pds-label" style="font-weight:400;padding:0">35.</span>
        <span class="pds-label" style="font-weight:400;padding:0;overflow:visible">a. Have you ever been found guilty of any administrative offense?</span>
    </div>
</td>
<td colspan="7" style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;padding:2.2mm 2mm 1mm">
    <div class="pds-answer-panel">
        <div class="pds-answer-options">
            <span class="pds-answer-option"><span class="pds-answer-box">{{ $q?->q35_a === null || $q?->q35_a === '' ? '' : ($q?->q35_a ? 'X' : '') }}</span>YES</span>
            <span class="pds-answer-option"><span class="pds-answer-box">{{ $q?->q35_a === null || $q?->q35_a === '' ? '' : (!$q?->q35_a ? 'X' : '') }}</span>NO</span>
        </div>
        <div>If YES, give details:</div>
        <div class="pds-answer-line">{{ pdsText($q?->q35_details) }}</div>
    </div>
</td>
</tr>
<tr style="height:20mm; --rh:20mm">
<td colspan="6" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;padding:1mm 1.6mm">
    <div class="pds-question-text">
        <span class="pds-label" style="font-weight:400;padding:0"></span>
        <span class="pds-label" style="font-weight:400;padding:0;overflow:visible">b. Have you been criminally charged before any court?</span>
    </div>
</td>
<td colspan="7" style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;padding:1.4mm 2mm 1mm">
    <div class="pds-answer-panel">
        <div class="pds-answer-options">
            <span class="pds-answer-option"><span class="pds-answer-box">{{ $q?->q35_b === null || $q?->q35_b === '' ? '' : ($q?->q35_b ? 'X' : '') }}</span>YES</span>
            <span class="pds-answer-option"><span class="pds-answer-box">{{ $q?->q35_b === null || $q?->q35_b === '' ? '' : (!$q?->q35_b ? 'X' : '') }}</span>NO</span>
        </div>
        <div>If YES, give details:</div>
        <div class="pds-answer-line"></div>
        <div style="display:grid;grid-template-columns:22mm 1fr;align-items:end;column-gap:1.5mm"><span>Date Filed:</span><span class="pds-answer-line">{{ pdsDate($q?->q35_date_filed) }}</span></div>
        <div style="display:grid;grid-template-columns:26mm 1fr;align-items:end;column-gap:1.5mm"><span>Status of Case/s:</span><span class="pds-answer-line">{{ pdsText($q?->q35_status) }}</span></div>
    </div>
</td>
</tr>
<tr style="height:5.271mm; --rh:5.271mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">36.</span></td>

<td rowspan="4" colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?</span></td>



<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value yes-no">{{ pdsYesNo($q?->q36) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
</tr>
<tr style="height:4.392mm; --rh:4.392mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td colspan="6" style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">     If YES, give details: </span><span class="pds-value">{{ pdsText($q?->q36_details) }}</span></td>





<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:4.392mm; --rh:4.392mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td colspan="4" style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>



<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:1.757mm; --rh:1.757mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td colspan="4" style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>



<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:4.172mm; --rh:4.172mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">37.</span></td>

<td rowspan="4" colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?</span></td>



<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value yes-no">{{ pdsYesNo($q?->q37) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
</tr>
<tr style="height:3.514mm; --rh:3.514mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td colspan="6" style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">     If YES, give details: </span><span class="pds-value">{{ pdsText($q?->q37_details) }}</span></td>





<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:3.294mm; --rh:3.294mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td colspan="4" style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>



<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:1.757mm; --rh:1.757mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:5.271mm; --rh:5.271mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">38.</span></td>

<td rowspan="2" colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">a. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?</span></td>



<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value yes-no">{{ pdsYesNo($q?->q38_a) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
</tr>
<tr style="height:3.514mm; --rh:3.514mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td colspan="4" style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:right;vertical-align:middle;font-size:7.20pt"><span class="pds-label">If YES, give details:</span></td>



<td colspan="2" style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-size:7.20pt"></td>

<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:1.976mm; --rh:1.976mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:4.172mm; --rh:4.172mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td rowspan="3" colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">b. Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?</span></td>



<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td colspan="4" style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-value yes-no">{{ pdsYesNo($q?->q38_b) }}</span></td>



<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:4.172mm; --rh:4.172mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td colspan="4" style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:right;vertical-align:middle;font-size:7.20pt"><span class="pds-label">If YES, give details:</span></td>



<td colspan="2" style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-size:7.20pt"></td>

<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:1.318mm; --rh:1.318mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:5.271mm; --rh:5.271mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">39.</span></td>

<td rowspan="4" colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:justify;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">Have you acquired the status of an immigrant or permanent resident of another country?</span></td>



<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"><span class="pds-value yes-no">{{ pdsYesNo($q?->q39) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
</tr>
<tr style="height:3.514mm; --rh:3.514mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td colspan="7" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">     If YES, give details (country): </span></td>






</tr>
<tr style="height:4.172mm; --rh:4.172mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td colspan="4" style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>



<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:1.757mm; --rh:1.757mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:right;vertical-align:middle;font-size:5.76pt"></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td colspan="4" style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>



<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
</tr>
<tr style="height:12.151mm; --rh:12.151mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">40.</span></td>

<td rowspan="2" colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">Pursuant to: (a) Indigenous People&#x27;s Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277, as amended); and (c) Expanded Solo Parents Welfare Act (RA 11861), please answer the following items:</span></td>



<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-weight:700;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"></td>
</tr>
<tr style="height:0.22mm; --rh:0.22mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"></td>




<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:3.953mm; --rh:3.953mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">a. </span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"></td>
<td rowspan="2" colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">Are you a member of any indigenous group?</span></td>



<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"><span class="pds-value yes-no">{{ pdsYesNo($q?->q40_a) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:4.392mm; --rh:4.392mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:top;font-size:7.20pt"><span class="pds-label">If YES, please specify:</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:top;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"><span class="pds-value">{{ pdsText($q?->q40_a_details) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:3.953mm; --rh:3.953mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">b. </span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"></td>
<td rowspan="2" colspan="4" style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">Are you a person with disability?</span></td>



<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"><span class="pds-value yes-no">{{ pdsYesNo($q?->q40_b) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:4.392mm; --rh:4.392mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:top;font-size:7.20pt"><span class="pds-label">If YES, please specify ID No: </span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:top;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"><span class="pds-value">{{ pdsText($q?->q40_b_details) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:3.953mm; --rh:3.953mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">c. </span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:right;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"></td>
<td colspan="2" style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">Are you a solo parent?</span></td>

<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.22mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"><span class="pds-value yes-no">{{ pdsYesNo($q?->q40_c) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:3.514mm; --rh:3.514mm">
<td rowspan="2" colspan="6" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:top;white-space:pre-wrap;font-size:5.76pt"></td>





<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;font-size:7.20pt"><span class="pds-label">If YES, please specify ID No: </span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:top;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"><span class="pds-value">{{ pdsText($q?->q40_c_details) }}</span></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:1.976mm; --rh:1.976mm">






<td style="border-left:0.22mm solid #000;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:6.808mm; --rh:6.808mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">41.</span></td>

<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-label">REFERENCES (Person not related by consanguinity or affinity to applicant /appointee)</span></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td rowspan="6" colspan="4" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0;text-align:center;vertical-align:middle;font-size:5.76pt"></td>



</tr>
<tr style="height:5.271mm; --rh:5.271mm">
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">NAME</span></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">OFFICE / RESIDENTIAL ADDRESS</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-label">CONTACT NO. AND/OR EMAIL</span></td>






</tr>
<tr style="height:7.027mm; --rh:7.027mm">
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($references->get(0)?->name) }}</span></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($references->get(0)?->address) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($references->get(0)?->telephone_no) }}</span></td>






</tr>
<tr style="height:7.027mm; --rh:7.027mm">
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($references->get(1)?->name) }}</span></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($references->get(1)?->address) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($references->get(1)?->telephone_no) }}</span></td>






</tr>
<tr style="height:7.027mm; --rh:7.027mm">
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.22mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:7.20pt"><span class="pds-value">{{ pdsText($references->get(2)?->name) }}</span></td>




<td style="border-left:0.22mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($references->get(2)?->address) }}</span></td>
<td colspan="3" style="border-left:0.22mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:6.48pt"><span class="pds-value">{{ pdsText($references->get(2)?->telephone_no) }}</span></td>






</tr>
<tr style="height:11.639mm; --rh:11.639mm">
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:center;vertical-align:top;font-size:5.76pt"><span class="pds-label">42.</span></td>

<td rowspan="3" colspan="6" style="border-left:0;border-right:0;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:justify;vertical-align:top;white-space:pre-wrap;font-size:9.00pt"><span class="pds-label">I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct, and complete statement pursuant to the provisions of pertinent laws, rules, and regulations of the Republic of the Philippines. I authorize the agency head/authorized representative to verify/validate the contents stated herein.          I  agree that any misrepresentation made in this document and its attachments shall cause the filing of administrative/criminal case/s against me.</span></td>





<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>




</tr>
<tr style="height:3.953mm; --rh:3.953mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:7.20pt"></td>






<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="2" style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:top;font-size:5.76pt"><span class="pds-value">{!! $photoMarkup !!}</span></td>

<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
</tr>
<tr style="height:3.953mm; --rh:3.953mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:7.20pt"></td>






<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td colspan="4" style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:center;vertical-align:top;font-size:5.76pt"></td>



</tr>
<tr style="height:2.196mm; --rh:2.196mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#EAEAEA;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#EAEAEA;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:left;vertical-align:top;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td rowspan="7" colspan="2" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"></td>

<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:2.416mm; --rh:2.416mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>


<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
</tr>
<tr style="height:9.516mm; --rh:9.516mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="3" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:5.04pt"><span class="pds-label">Government Issued ID (i.e.Passport, GSIS, SSS, PRC, Driver&#x27;s License, etc.)                               
PLEASE INDICATE ID Number and Date of Issuance</span></td>


<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td rowspan="4" colspan="4" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt;color:#FF0000"><span class="pds-value">{!! $signatureMarkup !!}</span></td>



<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>


<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
</tr>
<tr style="height:5.929mm; --rh:5.929mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-value">{{ pdsText($gov?->id_type) }}</span></td>

<td style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>




<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>


<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
</tr>
<tr style="height:2.635mm; --rh:2.635mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td rowspan="2" colspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-value">{{ pdsText($gov?->id_no) }}</span></td>

<td rowspan="2" style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>




<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>


<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
</tr>
<tr style="height:3.514mm; --rh:3.514mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>



<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>



<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>


<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
</tr>
<tr style="height:3.074mm; --rh:3.074mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td rowspan="2" colspan="2" style="border-left:0.55mm solid #000;border-right:0;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:5.76pt"><span class="pds-value">{{ pdsText($gov?->date_place_issuance) }}</span></td>

<td rowspan="2" style="border-left:0;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-weight:700;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.22mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>


<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
</tr>
<tr style="height:3.733mm; --rh:3.733mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>



<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td colspan="4" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"><span class="pds-value">{{ pdsDate(now()) }}</span></td>



<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="2" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;font-size:5.76pt"><span class="pds-label">Right Thumbmark</span></td>

<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
</tr>
<tr style="height:2.416mm; --rh:2.416mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:top;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:center;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
</tr>
<tr style="height:8.345mm; --rh:8.345mm">
<td colspan="13" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0;text-align:center;vertical-align:middle;font-size:6.48pt"><span class="pds-label">SUBSCRIBED AND SWORN to before me this                                                               , affiant exhibiting his/her validly issued government ID as indicated above.</span></td>












</tr>
<tr style="height:15.592mm; --rh:15.592mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:7.20pt"></td>
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.55mm solid #000;border-bottom:0.22mm solid #000;text-align:center;vertical-align:middle;white-space:pre-wrap;font-weight:700;font-size:5.04pt;color:#FF0000"><span class="pds-label">(wet signature/e-signature/digital certificate except for notary public)</span></td>




<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
</tr>
<tr style="height:5.271mm; --rh:5.271mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:center;vertical-align:middle;font-size:5.76pt"></td>
<td colspan="5" style="border-left:0.55mm solid #000;border-right:0.55mm solid #000;border-top:0.22mm solid #000;border-bottom:0.55mm solid #000;background:#e6e6e6;text-align:center;vertical-align:middle;white-space:pre-wrap;font-size:7.20pt"><span class="pds-label">Person Administering Oath</span></td>




<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;white-space:pre-wrap;font-size:5.76pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;background:#e6e6e6;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:7.20pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0;text-align:left;vertical-align:middle;font-size:5.76pt"></td>
</tr>
<tr style="height:3.294mm; --rh:3.294mm">
<td style="border-left:0.55mm solid #000;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
<td style="border-left:0;border-right:0.55mm solid #000;border-top:0;border-bottom:0.55mm solid #000;text-align:left;vertical-align:middle;font-size:4.80pt"></td>
</tr>
<tr style="height:3.074mm; --rh:3.074mm">
<td colspan="13" style="border-left:0;border-right:0;border-top:0;border-bottom:0;text-align:right;vertical-align:middle;font-style:italic;font-size:5.04pt"><span class="pds-label" style="white-space:nowrap">CS FORM 212 (Revised 2025), Page 4 of 4</span></td>












</tr>
</tbody></table>
</section>

<section class="page page-4 c4-copy" aria-label="PDS C4 official copy page">
    <div class="c4-page">
        <table class="c4-outer">
            <colgroup>
                <col style="width:126mm">
                <col style="width:76mm">
            </colgroup>
            <tbody>
                <tr style="height:27mm">
                    <td class="c4-question">
                        <div class="c4-qgrid">
                            <span class="c4-no">34.</span>
                            <div>
                                Are you related by consanguinity or affinity to the appointing or recommending authority, or to the<br>
                                chief of bureau or office or to the person who has immediate supervision over you in the Office,<br>
                                Bureau or Department where you will be appointed,<br>
                                <div style="margin-top:.8mm">a. within the third degree?</div>
                                <div style="margin-top:1mm">b. within the fourth degree (for Local Government Unit - Career Employees)?</div>
                            </div>
                        </div>
                    </td>
                    <td class="c4-answer">
                        <div class="c4-answer-inner" style="padding-top:4mm">
                            <div class="c4-options">
                                <span class="c4-option"><span class="c4-box">{{ $q?->q34_a === null || $q?->q34_a === '' ? '' : ($q?->q34_a ? 'X' : '') }}</span>YES</span>
                                <span class="c4-option"><span class="c4-box">{{ $q?->q34_a === null || $q?->q34_a === '' ? '' : (!$q?->q34_a ? 'X' : '') }}</span>NO</span>
                            </div>
                            <div class="c4-options">
                                <span class="c4-option"><span class="c4-box">{{ $q?->q34_b === null || $q?->q34_b === '' ? '' : ($q?->q34_b ? 'X' : '') }}</span>YES</span>
                                <span class="c4-option"><span class="c4-box">{{ $q?->q34_b === null || $q?->q34_b === '' ? '' : (!$q?->q34_b ? 'X' : '') }}</span>NO</span>
                            </div>
                            <div>If YES, give details:</div>
                            <span class="c4-line">{{ pdsText($q?->q34_details) }}</span>
                        </div>
                    </td>
                </tr>
                <tr style="height:39mm">
                    <td class="c4-question" style="padding:0">
                        <div style="height:16mm;padding:1mm 1.4mm;border-bottom:0.22mm solid #000">
                            <div class="c4-qgrid"><span class="c4-no">35.</span><span>a. Have you ever been found guilty of any administrative offense?</span></div>
                        </div>
                        <div style="height:23mm;padding:4mm 1.4mm 1mm">
                            <div class="c4-qgrid"><span></span><span>b. Have you been criminally charged before any court?</span></div>
                        </div>
                    </td>
                    <td class="c4-answer" style="padding:0">
                        <div style="height:16mm;padding:1.8mm 3.5mm;border-bottom:0.22mm solid #000">
                            <div class="c4-options">
                                <span class="c4-option"><span class="c4-box">{{ $q?->q35_a === null || $q?->q35_a === '' ? '' : ($q?->q35_a ? 'X' : '') }}</span>YES</span>
                                <span class="c4-option"><span class="c4-box">{{ $q?->q35_a === null || $q?->q35_a === '' ? '' : (!$q?->q35_a ? 'X' : '') }}</span>NO</span>
                            </div>
                            <div style="margin-top:1.4mm">If YES, give details:</div>
                            <span class="c4-line">{{ pdsText($q?->q35_details) }}</span>
                        </div>
                        <div style="height:23mm;padding:2.8mm 3.5mm 1mm">
                            <div class="c4-options">
                                <span class="c4-option"><span class="c4-box">{{ $q?->q35_b === null || $q?->q35_b === '' ? '' : ($q?->q35_b ? 'X' : '') }}</span>YES</span>
                                <span class="c4-option"><span class="c4-box">{{ $q?->q35_b === null || $q?->q35_b === '' ? '' : (!$q?->q35_b ? 'X' : '') }}</span>NO</span>
                            </div>
                            <div style="margin-top:1mm">If YES, give details:</div>
                            <div style="display:grid;grid-template-columns:23mm 1fr;align-items:end;margin-top:.6mm"><span style="text-align:right;padding-right:2mm">Date Filed:</span><span class="c4-line">{{ pdsDate($q?->q35_date_filed) }}</span></div>
                            <div style="display:grid;grid-template-columns:23mm 1fr;align-items:end"><span>Status of Case/s:</span><span class="c4-line">{{ pdsText($q?->q35_status) }}</span></div>
                        </div>
                    </td>
                </tr>
                <tr style="height:18mm">
                    <td class="c4-question"><div class="c4-qgrid"><span class="c4-no">36.</span><span>Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?</span></div></td>
                    <td class="c4-answer"><div class="c4-answer-inner"><div class="c4-options"><span class="c4-option"><span class="c4-box">{{ $q?->q36 === null || $q?->q36 === '' ? '' : ($q?->q36 ? 'X' : '') }}</span>YES</span><span class="c4-option"><span class="c4-box">{{ $q?->q36 === null || $q?->q36 === '' ? '' : (!$q?->q36 ? 'X' : '') }}</span>NO</span></div><div>If YES, give details:</div><span class="c4-line">{{ pdsText($q?->q36_details) }}</span></div></td>
                </tr>
                <tr style="height:16mm">
                    <td class="c4-question"><div class="c4-qgrid"><span class="c4-no">37.</span><span>Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?</span></div></td>
                    <td class="c4-answer"><div class="c4-answer-inner"><div class="c4-options"><span class="c4-option"><span class="c4-box">{{ $q?->q37 === null || $q?->q37 === '' ? '' : ($q?->q37 ? 'X' : '') }}</span>YES</span><span class="c4-option"><span class="c4-box">{{ $q?->q37 === null || $q?->q37 === '' ? '' : (!$q?->q37 ? 'X' : '') }}</span>NO</span></div><div>If YES, give details:</div><span class="c4-line">{{ pdsText($q?->q37_details) }}</span></div></td>
                </tr>
                <tr style="height:23mm">
                    <td class="c4-question" style="padding:0">
                        <div style="height:11.5mm;padding:1mm 1.4mm;border-bottom:0.22mm solid #000"><div class="c4-qgrid"><span class="c4-no">38.</span><span>a. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?</span></div></div>
                        <div style="height:11.5mm;padding:1mm 1.4mm"><div class="c4-qgrid"><span></span><span>b. Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?</span></div></div>
                    </td>
                    <td class="c4-answer" style="padding:0">
                        <div style="height:11.5mm;padding:1.1mm 3.5mm;border-bottom:0.22mm solid #000"><div class="c4-options"><span class="c4-option"><span class="c4-box">{{ $q?->q38_a === null || $q?->q38_a === '' ? '' : ($q?->q38_a ? 'X' : '') }}</span>YES</span><span class="c4-option"><span class="c4-box">{{ $q?->q38_a === null || $q?->q38_a === '' ? '' : (!$q?->q38_a ? 'X' : '') }}</span>NO</span></div><div>If YES, give details:</div><span class="c4-line">{{ pdsText($q?->q38_a_details) }}</span></div>
                        <div style="height:11.5mm;padding:1.1mm 3.5mm"><div class="c4-options"><span class="c4-option"><span class="c4-box">{{ $q?->q38_b === null || $q?->q38_b === '' ? '' : ($q?->q38_b ? 'X' : '') }}</span>YES</span><span class="c4-option"><span class="c4-box">{{ $q?->q38_b === null || $q?->q38_b === '' ? '' : (!$q?->q38_b ? 'X' : '') }}</span>NO</span></div><div>If YES, give details:</div><span class="c4-line">{{ pdsText($q?->q38_b_details) }}</span></div>
                    </td>
                </tr>
                <tr style="height:17mm">
                    <td class="c4-question"><div class="c4-qgrid"><span class="c4-no">39.</span><span>Have you acquired the status of an immigrant or permanent resident of another country?</span></div></td>
                    <td class="c4-answer"><div class="c4-answer-inner"><div class="c4-options"><span class="c4-option"><span class="c4-box">{{ $q?->q39 === null || $q?->q39 === '' ? '' : ($q?->q39 ? 'X' : '') }}</span>YES</span><span class="c4-option"><span class="c4-box">{{ $q?->q39 === null || $q?->q39 === '' ? '' : (!$q?->q39 ? 'X' : '') }}</span>NO</span></div><div>If YES, give details (country):</div><span class="c4-line">{{ pdsText($q?->q39_details) }}</span></div></td>
                </tr>
                <tr style="height:42mm">
                    <td class="c4-question">
                        <div class="c4-qgrid">
                            <span class="c4-no">40.</span>
                            <div>Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277, as amended); and (c) Expanded Solo Parents Welfare Act (RA 11861), please answer the following items:</div>
                        </div>
                        <div style="display:grid;grid-template-columns:5mm 1fr;row-gap:7mm;margin-top:4mm">
                            <span>a.</span><span>Are you a member of any indigenous group?</span>
                            <span>b.</span><span>Are you a person with disability?</span>
                            <span>c.</span><span>Are you a solo parent?</span>
                        </div>
                    </td>
                    <td class="c4-answer" style="padding:0">
                        <div style="height:14mm;padding:4.8mm 3.5mm 0"><div class="c4-options"><span class="c4-option"><span class="c4-box">{{ $q?->q40_a === null || $q?->q40_a === '' ? '' : ($q?->q40_a ? 'X' : '') }}</span>YES</span><span class="c4-option"><span class="c4-box">{{ $q?->q40_a === null || $q?->q40_a === '' ? '' : (!$q?->q40_a ? 'X' : '') }}</span>NO</span></div><div>If YES, please specify:<span class="c4-line" style="display:inline-block;width:30mm;margin-left:5mm">{{ pdsText($q?->q40_a_details) }}</span></div></div>
                        <div style="height:14mm;padding:2mm 3.5mm 0"><div class="c4-options"><span class="c4-option"><span class="c4-box">{{ $q?->q40_b === null || $q?->q40_b === '' ? '' : ($q?->q40_b ? 'X' : '') }}</span>YES</span><span class="c4-option"><span class="c4-box">{{ $q?->q40_b === null || $q?->q40_b === '' ? '' : (!$q?->q40_b ? 'X' : '') }}</span>NO</span></div><div>If YES, please specify ID No:<span class="c4-line" style="display:inline-block;width:30mm;margin-left:3mm">{{ pdsText($q?->q40_b_details) }}</span></div></div>
                        <div style="height:14mm;padding:2mm 3.5mm 0"><div class="c4-options"><span class="c4-option"><span class="c4-box">{{ $q?->q40_c === null || $q?->q40_c === '' ? '' : ($q?->q40_c ? 'X' : '') }}</span>YES</span><span class="c4-option"><span class="c4-box">{{ $q?->q40_c === null || $q?->q40_c === '' ? '' : (!$q?->q40_c ? 'X' : '') }}</span>NO</span></div><div>If YES, please specify ID No:<span class="c4-line" style="display:inline-block;width:30mm;margin-left:3mm">{{ pdsText($q?->q40_c_details) }}</span></div></div>
                    </td>
                </tr>
                <tr style="height:78mm">
                    <td colspan="2" style="padding:0;border-bottom:0.55mm solid #000">
                        <table>
                            <colgroup>
                                <col style="width:151mm">
                                <col style="width:51mm">
                            </colgroup>
                            <tr>
                                <td style="padding:0;border:0">
                                    <table class="c4-ref">
                                        <colgroup>
                                            <col style="width:79mm">
                                            <col style="width:49mm">
                                            <col style="width:23mm">
                                        </colgroup>
                                        <tr><td colspan="3" class="c4-ref-title" style="text-align:left;vertical-align:middle">41. &nbsp; REFERENCES <strong>(Person not related by consanguinity or affinity to applicant /appointee)</strong></td></tr>
                                        <tr><th>NAME</th><th>OFFICE / RESIDENTIAL ADDRESS</th><th>CONTACT NO. AND/OR EMAIL</th></tr>
                                        @for($i = 0; $i < 3; $i++)
                                            <tr>
                                                <td>{{ pdsText($references->get($i)?->name) }}</td>
                                                <td>{{ pdsText($references->get($i)?->address) }}</td>
                                                <td>{{ pdsText($references->get($i)?->telephone_no) }}</td>
                                            </tr>
                                        @endfor
                                    </table>
                                    <div class="c4-declaration">
                                        <div class="c4-declaration-grid">
                                            <span>42.</span>
                                            <p>I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct, and complete statement pursuant to the provisions of pertinent laws, rules, and regulations of the Republic of the Philippines. I authorize the agency head/authorized representative to verify/validate the contents stated herein. I agree that any misrepresentation made in this document and its attachments shall cause the filing of administrative/criminal case/s against me.</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="c4-photo-cell" rowspan="2">
                                    <div class="c4-photo-box">
                                        @if($photoUrl)
                                            <img src="{{ $photoUrl }}" alt="PDS photo">
                                        @else
                                            <span>Passport-sized unfiltered digital<br>picture taken within<br>the last 6 months<br>4.5 cm. X 3.5 cm</span>
                                        @endif
                                    </div>
                                    <div class="c4-photo-label">PHOTO</div>
                                    <div class="c4-thumb-box">
                                        <div class="c4-thumb-area"></div>
                                        <div class="c4-caption">Right Thumbmark</div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="c4-bottom-cell">
                                    <table class="c4-bottom">
                                        <colgroup>
                                            <col style="width:71mm">
                                            <col style="width:4mm">
                                            <col style="width:73mm">
                                        </colgroup>
                                        <tr>
                                            <td>
                                                <div class="c4-id-box">
                                                    <table>
                                                        <tr><td class="c4-id-heading">Government Issued ID <span class="c4-small">(i.e.Passport, GSIS, SSS, PRC, Driver's License, etc.)</span><br><em>PLEASE INDICATE ID Number and Date of Issuance</em></td></tr>
                                                        <tr><td>Government Issued ID: <span style="float:right">{{ pdsText($gov?->id_type) }}</span></td></tr>
                                                        <tr><td>ID/License/Passport No.: <span style="float:right">{{ pdsText($gov?->id_no) }}</span></td></tr>
                                                        <tr><td>Date/Place of Issuance: <span style="float:right">{{ pdsText($gov?->date_place_issuance) }}</span></td></tr>
                                                    </table>
                                                </div>
                                            </td>
                                            <td></td>
                                            <td>
                                                <div class="c4-sign-box">
                                                    <div class="c4-sign-main">{!! $signatureMarkup !!}</div>
                                                    <div class="c4-caption">Signature (Sign inside the box)</div>
                                                    <div class="c4-caption c4-date-value">{{ now()->format('d/m/Y') }}</div>
                                                    <div class="c4-caption">Date Accomplished</div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr style="height:31mm">
                    <td colspan="2" class="c4-oath">
                        SUBSCRIBED AND SWORN to before me this &nbsp; ________________________________, affiant exhibiting his/her validly issued government ID as indicated above.
                        <div class="c4-oath-admin">
                            <div class="c4-sign-main">(wet signature/e-signature/digital certificate except for notary public)</div>
                            <div class="c4-caption">Person Administering Oath</div>
                        </div>
                    </td>
                </tr>
                <tr class="c4-footer-row" style="height:3mm">
                    <td colspan="2" class="c4-footer">CS FORM 212 (Revised 2025), Page 4 of 4</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="c4-page-footer">CS FORM 212 (Revised 2025), Page 4 of 4</div>
</section>

@php
    $overflow = [
        'Children' => $children->slice(12),
        'Civil Service Eligibility' => $eligibilities->slice(7),
        'Work Experience' => $workExperiences->slice(28),
        'Voluntary Work' => $voluntaryWorks->slice(7),
        'Learning and Development' => $trainings->slice(21),
        'Special Skills' => $skills->slice(7),
        'Distinctions' => $distinctions->slice(7),
        'Memberships' => $memberships->slice(7),
        'References' => $references->slice(3),
    ];
@endphp
@foreach($overflow as $title => $rows)
    @if($rows->count())
        <section class="page continuation">
            <h2>CS Form 212 Revised 2025 - Continuation Sheet</h2>
            <table>
                <thead><tr><th colspan="4">{{ $title }}</th></tr></thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @if($title === 'Children')
                                <td>{{ $row->fullname }}</td><td>{{ pdsDate($row->date_of_birth) }}</td><td colspan="2"></td>
                            @elseif($title === 'Civil Service Eligibility')
                                <td>{{ $row->title }}</td><td>{{ $row->rating }}</td><td>{{ pdsDate($row->date_of_exam) }}</td><td>{{ $row->place_of_exam }}</td>
                            @elseif($title === 'Work Experience')
                                <td>{{ pdsDate($row->date_from) }} - {{ pdsDate($row->date_to) }}</td><td>{{ $row->position_title }}</td><td>{{ $row->company }}</td><td>{{ $row->appointment_status }}</td>
                            @elseif($title === 'Voluntary Work')
                                <td>{{ $row->organization_name }}</td><td>{{ pdsDate($row->date_from) }} - {{ pdsDate($row->date_to) }}</td><td>{{ $row->number_of_hours }}</td><td>{{ $row->position }}</td>
                            @elseif($title === 'Learning and Development')
                                <td>{{ $row->title }}</td><td>{{ pdsDate($row->date_from) }} - {{ pdsDate($row->date_to) }}</td><td>{{ $row->number_of_hours }}</td><td>{{ $row->conducted_by }}</td>
                            @elseif($title === 'References')
                                <td>{{ $row->name }}</td><td colspan="2">{{ $row->address }}</td><td>{{ $row->telephone_no }}</td>
                            @else
                                <td colspan="4">{{ $row->description }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endforeach
</div>
<script>
const waitForPdsImages = () => Promise.all(Array.from(document.images).map((img) => {
    if (img.complete) return Promise.resolve();

    return new Promise((resolve) => {
        img.addEventListener('load', resolve, { once: true });
        img.addEventListener('error', resolve, { once: true });
    });
}));

document.addEventListener('DOMContentLoaded', async () => {
    let restorePagedModeAfterPrint = false;

    const cleanMergedLabels = () => {
        const sectionLabels = [
            'I. PERSONAL INFORMATION',
            'II. FAMILY BACKGROUND',
            'III. EDUCATIONAL BACKGROUND',
            'IV. CIVIL SERVICE ELIGIBILITY',
            'V. WORK EXPERIENCE',
            'VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATION/S',
            'VII. LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED',
            'VIII. OTHER INFORMATION'
        ];
        const wideRowLabelPatterns = [
            /PRINT\s+LEGIBLY/,
            /RESIDENTIAL\s+ADDRESS/,
            /PERMANENT\s+ADDRESS/,
            /PHILHEALTH\s+NO/,
            /PHILSYS\s+NUMBER/,
            /TELEPHONE\s+NO/,
            /MOBILE\s+NO/,
            /E-?MAIL\s+ADDRESS/,
            /AGENCY\s+EMPLOYEE\s+NO/
        ];

        document.querySelectorAll('.pds-label').forEach((label) => {
            const normalized = label.textContent.replace(/\s+/g, ' ').trim().toUpperCase();
            const cell = label.closest('td');
            if (!cell) return;

            if (wideRowLabelPatterns.some((pattern) => pattern.test(normalized))) {
                cell.classList.add('pds-label-cell-wide');
                label.style.whiteSpace = 'nowrap';
            }

            if (sectionLabels.some((text) => normalized === text || normalized.startsWith(text))) {
                cell.classList.add('pds-section-cell-wide');
                label.style.whiteSpace = 'nowrap';
            }
        });
    };

    const shrinkValues = () => {
        document.querySelectorAll('.pds-value').forEach((el) => {
            if (!el.textContent.trim()) return;
            let size = parseFloat(getComputedStyle(el).fontSize);
                while ((el.scrollWidth > el.clientWidth || el.scrollHeight > el.clientHeight) && size > 4.6) {
                    size -= 0.25;
                    el.style.fontSize = size + 'px';
                }
            });
        };

        const fitExcelSheets = () => {
            document.querySelectorAll('.page:not(.continuation)').forEach((page) => {
                const table = page.querySelector('table.pds-grid');
                if (!table) return;

                table.style.transform = '';
                const pageBox = page.getBoundingClientRect();
                const tableBox = table.getBoundingClientRect();
                const pageStyle = getComputedStyle(page);
                const availableHeight = pageBox.height - parseFloat(pageStyle.paddingTop) - parseFloat(pageStyle.paddingBottom) - 1;
                const availableWidth = pageBox.width - parseFloat(pageStyle.paddingLeft) - parseFloat(pageStyle.paddingRight) - 1;
                const heightScale = Math.min(1, availableHeight / tableBox.height);
                const widthScale = Math.min(1, availableWidth / tableBox.width);

                if (page.classList.contains('page-4') && heightScale < 0.999 && widthScale > 0.999) {
                    table.style.transform = `scaleY(${Math.max(heightScale, 0.90)})`;
                } else {
                    const scale = Math.min(1, heightScale, widthScale);
                    if (scale < 0.999) {
                        table.style.transform = `scale(${Math.max(scale, 0.90)})`;
                    }
                }
            });
        };

        const setupPdsPager = () => {
            const documentEl = document.querySelector('.pds-document');
            const controls = document.querySelector('[data-pds-page-controls]');
            const prevButton = document.querySelector('[data-pds-prev-page]');
            const nextButton = document.querySelector('[data-pds-next-page]');
            const indicator = document.querySelector('[data-pds-page-indicator]');

            if (!documentEl || !controls || !prevButton || !nextButton || !indicator) {
                return;
            }

            const pages = Array.from(documentEl.querySelectorAll('.page'))
                .filter((page) => !page.classList.contains('pds-old-c4'));

            if (pages.length <= 1) {
                controls.style.display = 'none';
                return;
            }

            let currentPage = Math.max(0, pages.findIndex((page) => page.classList.contains('is-active')));

            if (currentPage < 0) {
                currentPage = 0;
            }

            const renderPage = (index) => {
                currentPage = Math.min(Math.max(index, 0), pages.length - 1);

                documentEl.classList.add('is-paged');
                pages.forEach((page, pageIndex) => {
                    page.classList.toggle('is-active', pageIndex === currentPage);
                });

                prevButton.disabled = currentPage === 0;
                nextButton.disabled = currentPage === pages.length - 1;
                indicator.textContent = `Page ${currentPage + 1} of ${pages.length}`;
            };

            prevButton.addEventListener('click', () => renderPage(currentPage - 1));
            nextButton.addEventListener('click', () => renderPage(currentPage + 1));
            renderPage(currentPage);
        };

        const disablePagedModeForPrint = () => {
            const documentEl = document.querySelector('.pds-document');
            if (!documentEl) {
                return;
            }

            restorePagedModeAfterPrint = documentEl.classList.contains('is-paged');
            documentEl.classList.remove('is-paged');
        };

        const restorePagedMode = () => {
            if (restorePagedModeAfterPrint) {
                document.querySelector('.pds-document')?.classList.add('is-paged');
            }
        };

    cleanMergedLabels();
    shrinkValues();
    fitExcelSheets();
    setupPdsPager();
    await waitForPdsImages();
    window.addEventListener('beforeprint', () => {
        disablePagedModeForPrint();
        cleanMergedLabels();
        shrinkValues();
        fitExcelSheets();
    });
    window.addEventListener('afterprint', restorePagedMode);
});
</script>
        </div>
    </div>
</div>
</x-app-layout>

