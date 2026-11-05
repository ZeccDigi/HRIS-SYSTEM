@php
    use App\Support\PdsFormatter;
    $p = $employee->pdsPersonal;
    $f = $employee->pdsFamily;
    $q = $employee->pdsQuestionnaire;
    $gov = $employee->pdsGovId;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>CS Form 212 - {{ $p?->surname ?? $employee->lastname }}</title>
    <style>
        @page { size: 8.5in 14in; margin: 10mm 8mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 7.5pt; line-height: 1.15; color: #000; margin: 0; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .italic { font-style: italic; }
        .small { font-size: 6.5pt; }
        .title { font-size: 11pt; font-weight: bold; }
        .subtitle { font-size: 9pt; font-weight: bold; }
        .warning { font-size: 6.5pt; text-align: justify; margin: 4px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th, td { border: 1px solid #000; padding: 2px 3px; vertical-align: top; }
        .section { background: #e8e8e8; font-weight: bold; font-style: italic; text-transform: uppercase; }
        .label { font-weight: bold; font-size: 7pt; }
        .no-border { border: none !important; }
        .photo-box { width: 95px; height: 120px; border: 1px solid #000; text-align: center; font-size: 6pt; padding: 4px; }
        .photo-box img { width: 100%; height: 85px; object-fit: cover; }
        .footer { font-size: 6.5pt; text-align: right; margin-top: 6px; font-style: italic; }
    </style>
</head>
<body>

{{-- PAGE 1 --}}
<div class="page">
    <div class="center">
        <div class="small">CS Form No. 212</div>
        <div class="title">PERSONAL DATA SHEET</div>
        <div class="small bold">Revised 2017</div>
    </div>
    <p class="warning">
        <span class="bold">WARNING:</span> Any misinterpretation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal case/s against the person concerned.
        READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA SHEET (PDS) BEFORE ACCOMPLISHING THE PDS FORM.
        Print legibly. Tick appropriate boxes and use separate sheet if necessary. Indicate N/A if not applicable. DO NOT ABBREVIATE.
    </p>

    <table>
        <tr><td colspan="6" class="section">I. PERSONAL INFORMATION</td></tr>
        <tr>
            <td class="label" width="14%">2. SURNAME</td>
            <td colspan="2">{{ PdsFormatter::val($p?->surname ?? $employee->lastname) }}</td>
            <td class="label" width="12%">1. CS ID No.</td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td class="label">FIRST NAME</td>
            <td>{{ PdsFormatter::val($p?->firstname ?? $employee->firstname) }}</td>
            <td class="label">NAME EXT.</td>
            <td colspan="3">{{ PdsFormatter::val($p?->name_extension) }}</td>
        </tr>
        <tr>
            <td class="label">MIDDLE NAME</td>
            <td colspan="5">{{ PdsFormatter::val($p?->middlename ?? $employee->middlename) }}</td>
        </tr>
        <tr>
            <td class="label">3. DATE OF BIRTH</td>
            <td>{{ PdsFormatter::date($p?->date_of_birth) }}</td>
            <td class="label">4. PLACE OF BIRTH</td>
            <td colspan="3">{{ PdsFormatter::val($p?->place_of_birth) }}</td>
        </tr>
        <tr>
            <td class="label">5. SEX</td>
            <td>{{ PdsFormatter::val($p?->sex) }} &nbsp; {{ PdsFormatter::check($p?->sex, 'Male') }} Male &nbsp; {{ PdsFormatter::check($p?->sex, 'Female') }} Female</td>
            <td class="label">6. CIVIL STATUS</td>
            <td colspan="3">{{ PdsFormatter::val($p?->civil_status) }}</td>
        </tr>
        <tr>
            <td class="label">7. HEIGHT (m)</td>
            <td>{{ PdsFormatter::val($p?->height_m) }}</td>
            <td class="label">8. WEIGHT (kg)</td>
            <td>{{ PdsFormatter::val($p?->weight_kg) }}</td>
            <td class="label">9. BLOOD TYPE</td>
            <td>{{ PdsFormatter::val($p?->blood_type) }}</td>
        </tr>
        <tr>
            <td class="label">10. GSIS ID NO.</td>
            <td>{{ PdsFormatter::val($p?->gsis_id_no) }}</td>
            <td class="label">11. PAG-IBIG ID NO.</td>
            <td>{{ PdsFormatter::pagibigIdNo($p?->pagibig_id_no) }}</td>
            <td class="label">12. PHILHEALTH NO.</td>
            <td>{{ PdsFormatter::philhealthNo($p?->philhealth_no) }}</td>
        </tr>
        <tr>
            <td class="label">13. SSS NO.</td>
            <td>{{ PdsFormatter::sssNo($p?->sss_no) }}</td>
            <td class="label">14. TIN NO.</td>
            <td>{{ PdsFormatter::tinNo($p?->tin_no) }}</td>
            <td class="label">15. AGENCY EMP. NO.</td>
            <td>{{ PdsFormatter::val($p?->agency_employee_no) }}</td>
        </tr>
        <tr>
            <td class="label" colspan="6">16. CITIZENSHIP: {{ PdsFormatter::val($p?->citizenship, 'Filipino') }}
                @if($p?->citizenship_type) — {{ $p->citizenship_type }} @endif
                @if($p?->citizenship_country) ({{ $p->citizenship_country }}) @endif
            </td>
        </tr>
        <tr>
            <td class="label" colspan="6">17. RESIDENTIAL ADDRESS:
                {{ PdsFormatter::val(trim(implode(', ', array_filter([
                    $p?->res_house_no, $p?->res_street, $p?->res_subdivision, $p?->res_barangay,
                    $p?->res_city, $p?->res_province, $p?->res_zip_code ? 'ZIP '.$p->res_zip_code : null,
                ])))) }}
            </td>
        </tr>
        <tr>
            <td class="label" colspan="6">18. PERMANENT ADDRESS:
                {{ PdsFormatter::val(trim(implode(', ', array_filter([
                    $p?->perm_house_no, $p?->perm_street, $p?->perm_subdivision, $p?->perm_barangay,
                    $p?->perm_city, $p?->perm_province, $p?->perm_zip_code ? 'ZIP '.$p->perm_zip_code : null,
                ])))) }}
            </td>
        </tr>
        <tr>
            <td class="label">19. TELEPHONE NO.</td>
            <td>{{ PdsFormatter::val($p?->telephone_no) }}</td>
            <td class="label">20. MOBILE NO.</td>
            <td>{{ PdsFormatter::val($p?->mobile_no ?? $employee->contact_number) }}</td>
            <td class="label">21. E-MAIL</td>
            <td>{{ PdsFormatter::val($p?->email_address ?? $employee->user?->email) }}</td>
        </tr>
    </table>

    <table>
        <tr><td colspan="4" class="section">II. FAMILY BACKGROUND</td></tr>
        <tr>
            <td class="label">22. SPOUSE'S SURNAME</td>
            <td colspan="3">{{ PdsFormatter::val($f?->spouse_surname) }}</td>
        </tr>
        <tr>
            <td class="label">FIRST NAME</td><td>{{ PdsFormatter::val($f?->spouse_firstname) }}</td>
            <td class="label">MIDDLE NAME</td><td>{{ PdsFormatter::val($f?->spouse_middlename) }}</td>
        </tr>
        <tr>
            <td class="label">OCCUPATION</td><td>{{ PdsFormatter::val($f?->spouse_occupation) }}</td>
            <td class="label">EMPLOYER/BUSINESS</td><td>{{ PdsFormatter::val($f?->spouse_employer) }}</td>
        </tr>
        <tr>
            <td class="label">24. FATHER'S SURNAME</td>
            <td>{{ PdsFormatter::val($f?->father_surname) }}</td>
            <td class="label">FIRST / MIDDLE</td>
            <td>{{ PdsFormatter::val($f?->father_firstname) }} {{ PdsFormatter::val($f?->father_middlename) }}</td>
        </tr>
        <tr>
            <td class="label">25. MOTHER'S MAIDEN NAME</td>
            <td colspan="3">{{ PdsFormatter::val($f?->mother_maiden_surname) }} {{ PdsFormatter::val($f?->mother_firstname) }} {{ PdsFormatter::val($f?->mother_middlename) }}</td>
        </tr>
    </table>

    <table>
        <tr><td colspan="2" class="section">23. NAME OF CHILDREN (Write full name and list all)</td></tr>
        <tr class="bold center"><td width="70%">FULL NAME</td><td>DATE OF BIRTH (mm/dd/yyyy)</td></tr>
        @forelse($employee->pdsChildren as $child)
            <tr><td>{{ $child->fullname }}</td><td>{{ PdsFormatter::date($child->date_of_birth) }}</td></tr>
        @empty
            <tr><td colspan="2" class="center italic">N/A</td></tr>
        @endforelse
    </table>

    <table>
        <tr><td colspan="7" class="section">III. EDUCATIONAL BACKGROUND</td></tr>
        <tr class="bold center small">
            <td>LEVEL</td><td>NAME OF SCHOOL</td><td>DEGREE/COURSE</td><td>FROM</td><td>TO</td><td>YEAR GRAD</td><td>HONORS</td>
        </tr>
        @forelse($employee->pdsEducation as $edu)
            <tr>
                <td>{{ $edu->level }}</td>
                <td>{{ $edu->school_name }}</td>
                <td>{{ PdsFormatter::val($edu->course) }}</td>
                <td>{{ PdsFormatter::val($edu->period_from) }}</td>
                <td>{{ PdsFormatter::val($edu->period_to) }}</td>
                <td>{{ PdsFormatter::val($edu->year_graduated) }}</td>
                <td>{{ PdsFormatter::val($edu->honors) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="center italic">N/A</td></tr>
        @endforelse
    </table>
    <div class="footer">CS FORM 212 (Revised 2017), Page 1 of 4</div>
</div>

{{-- PAGE 2 --}}
<div class="page">
    <table>
        <tr><td colspan="6" class="section">IV. CIVIL SERVICE ELIGIBILITY</td></tr>
        <tr class="bold center small">
            <td>CAREER SERVICE / ELIGIBILITY</td><td>RATING</td><td>DATE OF EXAM</td><td>PLACE</td><td>LICENSE NO.</td><td>VALIDITY</td>
        </tr>
        @forelse($employee->pdsEligibilities as $eli)
            <tr>
                <td>{{ $eli->title }}</td>
                <td>{{ PdsFormatter::val($eli->rating) }}</td>
                <td>{{ PdsFormatter::date($eli->date_of_exam) }}</td>
                <td>{{ PdsFormatter::val($eli->place_of_exam) }}</td>
                <td>{{ PdsFormatter::val($eli->license_number) }}</td>
                <td>{{ PdsFormatter::date($eli->license_validity) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="center italic">N/A</td></tr>
        @endforelse
    </table>

    <table>
        <tr><td colspan="7" class="section">V. WORK EXPERIENCE</td></tr>
        <tr class="bold center small">
            <td>FROM</td><td>TO</td><td>POSITION TITLE</td><td>DEPARTMENT/AGENCY/COMPANY</td><td>MONTHLY SALARY</td><td>STATUS</td><td>GOV'T</td>
        </tr>
        @forelse($employee->pdsWorkExperiences as $work)
            <tr>
                <td>{{ PdsFormatter::date($work->date_from) }}</td>
                <td>{{ $work->date_to ? PdsFormatter::date($work->date_to) : 'PRESENT' }}</td>
                <td>{{ $work->position_title }}</td>
                <td>{{ $work->company }}</td>
                <td>{{ PdsFormatter::val($work->monthly_salary) }}</td>
                <td>{{ PdsFormatter::val($work->appointment_status) }}</td>
                <td class="center">{{ $work->is_gov_service ? 'Y' : 'N' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="center italic">N/A</td></tr>
        @endforelse
    </table>
    <div class="footer">CS FORM 212 (Revised 2017), Page 2 of 4</div>
</div>

{{-- PAGE 3 --}}
<div class="page">
    <table>
        <tr><td colspan="5" class="section">VI. VOLUNTARY WORK</td></tr>
        <tr class="bold center small">
            <td>ORGANIZATION</td><td>FROM</td><td>TO</td><td>HOURS</td><td>POSITION</td>
        </tr>
        @forelse($employee->pdsVoluntaryWorks as $vol)
            <tr>
                <td>{{ $vol->organization_name }}</td>
                <td>{{ PdsFormatter::date($vol->date_from) }}</td>
                <td>{{ PdsFormatter::date($vol->date_to) }}</td>
                <td>{{ PdsFormatter::val($vol->number_of_hours) }}</td>
                <td>{{ PdsFormatter::val($vol->position) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="center italic">N/A</td></tr>
        @endforelse
    </table>

    <table>
        <tr><td colspan="5" class="section">VII. LEARNING AND DEVELOPMENT (TRAINING)</td></tr>
        <tr class="bold center small">
            <td>PROGRAM TITLE</td><td>FROM</td><td>TO</td><td>HOURS</td><td>CONDUCTED BY</td>
        </tr>
        @forelse($employee->pdsTrainings as $train)
            <tr>
                <td>{{ $train->title }}</td>
                <td>{{ PdsFormatter::date($train->date_from) }}</td>
                <td>{{ PdsFormatter::date($train->date_to) }}</td>
                <td>{{ PdsFormatter::val($train->number_of_hours) }}</td>
                <td>{{ PdsFormatter::val($train->conducted_by) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="center italic">N/A</td></tr>
        @endforelse
    </table>

    <table>
        <tr><td colspan="3" class="section">VIII. OTHER INFORMATION</td></tr>
        <tr class="bold center"><td>SPECIAL SKILLS / HOBBIES</td><td>DISTINCTIONS</td><td>MEMBERSHIP</td></tr>
        <tr>
            <td>@foreach($employee->pdsOthers->where('type', 'Skill') as $item)• {{ $item->description }}<br>@endforeach</td>
            <td>@foreach($employee->pdsOthers->where('type', 'Distinction') as $item)• {{ $item->description }}<br>@endforeach</td>
            <td>@foreach($employee->pdsOthers->where('type', 'Membership') as $item)• {{ $item->description }}<br>@endforeach</td>
        </tr>
    </table>

    @if($q)
    <table>
        <tr><td colspan="2" class="section">IX. YES/NO QUESTIONS (34–40)</td></tr>
        <tr><td width="85%">34. Related to authority within 3rd degree?</td><td>{{ ($q->q34_a || $q->q34_b) ? 'YES' : 'NO' }}</td></tr>
        <tr><td>35. Found guilty of administrative offense?</td><td>{{ ($q->q35_a || $q->q35_b) ? 'YES' : 'NO' }}</td></tr>
        <tr><td>36. Criminally charged?</td><td>{{ $q->q36 ? 'YES' : 'NO' }}</td></tr>
        <tr><td>37. Convicted of crime?</td><td>{{ $q->q37 ? 'YES' : 'NO' }}</td></tr>
        <tr><td>38. Separated from service?</td><td>{{ ($q->q38_a || $q->q38_b) ? 'YES' : 'NO' }}</td></tr>
        <tr><td>39. Candidate for election?</td><td>{{ $q->q39 ? 'YES' : 'NO' }}</td></tr>
        <tr><td>40a. Indigenous group member?</td><td>{{ $q->q40_a ? 'YES' : 'NO' }}</td></tr>
        <tr><td>40b. Person with disability?</td><td>{{ $q->q40_b ? 'YES' : 'NO' }}</td></tr>
        <tr><td>40c. Solo parent?</td><td>{{ $q->q40_c ? 'YES' : 'NO' }}</td></tr>
    </table>
    @endif
    <div class="footer">CS FORM 212 (Revised 2017), Page 3 of 4</div>
</div>

{{-- PAGE 4 --}}
<div class="page">
    <table>
        <tr><td colspan="3" class="section">41. REFERENCES</td></tr>
        <tr class="bold center"><td>NAME</td><td>ADDRESS</td><td>TEL. NO.</td></tr>
        @forelse($employee->pdsReferences as $ref)
            <tr><td>{{ $ref->name }}</td><td>{{ $ref->address }}</td><td>{{ $ref->telephone_no }}</td></tr>
        @empty
            <tr><td colspan="3" class="center italic">N/A</td></tr>
        @endforelse
    </table>

    <table>
        <tr>
            <td width="68%" class="small" style="vertical-align: top;">
                <p class="bold">42.</p>
                <p style="text-align: justify;">
                    I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct and complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines.
                    I authorize the agency head/authorized representative to verify/validate the contents stated herein.
                </p>
                <p style="margin-top: 20px;">
                    Government Issued ID: {{ PdsFormatter::val($gov?->id_type) }}<br>
                    ID/License/Passport No.: {{ PdsFormatter::val($gov?->id_no) }}<br>
                    Date/Place of Issuance: {{ PdsFormatter::val($gov?->date_place_issuance) }}
                </p>
                <p style="margin-top: 30px; border-top: 1px solid #000; width: 80%; padding-top: 4px;">Signature (Sign inside the box)</p>
                <p>Date Accomplished: {{ now()->format('m/d/Y') }}</p>
            </td>
            <td width="32%" class="center" style="vertical-align: top;">
                <div class="photo-box" style="margin: 0 auto;">
                    @if($employee->profile_picture_absolute_path)
                        <img src="{{ $employee->profile_picture_absolute_path }}" alt="Photo">
                    @else
                        <div style="height: 85px; line-height: 85px;">PHOTO</div>
                    @endif
                    <div style="margin-top: 4px;">3.5 cm x 4.5 cm<br>(passport size)</div>
                </div>
            </td>
        </tr>
    </table>
    <div class="footer">CS FORM 212 (Revised 2017), Page 4 of 4</div>
</div>

</body>
</html>
