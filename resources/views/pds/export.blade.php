<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Personal Data Sheet - {{ $employee->lastname }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.2;
        }
        .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .form-title {
            font-size: 14pt;
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }
        .section-header {
            background-color: #e0e0e0;
            font-weight: bold;
            text-transform: uppercase;
            font-style: italic;
        }
        .label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 30%;
        }
        .value {
            width: 70%;
        }
        .multi-row th {
            background-color: #f5f5f5;
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="form-title">CS Form No. 212</div>
        <div style="font-size: 12pt;">PERSONAL DATA SHEET</div>
    </div>

    <!-- I. PERSONAL INFORMATION -->
    <table>
        <tr>
            <td colspan="4" class="section-header">I. PERSONAL INFORMATION</td>
        </tr>
        <tr>
            <td class="label">SURNAME</td>
            <td colspan="3" class="value">{{ $employee->pdsPersonal->surname ?? $employee->lastname }}</td>
        </tr>
        <tr>
            <td class="label">FIRST NAME</td>
            <td class="value">{{ $employee->pdsPersonal->firstname ?? $employee->firstname }}</td>
            <td class="label">NAME EXTENSION</td>
            <td class="value">{{ $employee->pdsPersonal->name_extension ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">MIDDLE NAME</td>
            <td colspan="3" class="value">{{ $employee->pdsPersonal->middlename ?? $employee->middlename ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">DATE OF BIRTH</td>
            <td class="value">{{ $employee->pdsPersonal->date_of_birth ?? '---' }}</td>
            <td class="label">PLACE OF BIRTH</td>
            <td class="value">{{ $employee->pdsPersonal->place_of_birth ?? '---' }}</td>
        </tr>
        <tr>
            <td class="label">SEX</td>
            <td class="value">{{ $employee->pdsPersonal->sex ?? '---' }}</td>
            <td class="label">CIVIL STATUS</td>
            <td class="value">{{ $employee->pdsPersonal->civil_status ?? '---' }}</td>
        </tr>
        <tr>
            <td class="label">HEIGHT (m)</td>
            <td class="value">{{ $employee->pdsPersonal->height_m ?? '---' }}</td>
            <td class="label">WEIGHT (kg)</td>
            <td class="value">{{ $employee->pdsPersonal->weight_kg ?? '---' }}</td>
        </tr>
        <tr>
            <td class="label">BLOOD TYPE</td>
            <td class="value">{{ $employee->pdsPersonal->blood_type ?? '---' }}</td>
            <td class="label">GSIS ID NO.</td>
            <td class="value">{{ $employee->pdsPersonal->gsis_id_no ?? '---' }}</td>
        </tr>
        <tr>
            <td class="label">PAG-IBIG ID NO.</td>
            <td class="value">{{ \App\Support\PdsFormatter::pagibigIdNo($employee->pdsPersonal?->pagibig_id_no, '---') }}</td>
            <td class="label">PHILHEALTH NO.</td>
            <td class="value">{{ \App\Support\PdsFormatter::philhealthNo($employee->pdsPersonal?->philhealth_no, '---') }}</td>
        </tr>
        <tr>
            <td class="label">SSS NO.</td>
            <td class="value">{{ \App\Support\PdsFormatter::sssNo($employee->pdsPersonal?->sss_no, '---') }}</td>
            <td class="label">TIN NO.</td>
            <td class="value">{{ \App\Support\PdsFormatter::tinNo($employee->pdsPersonal?->tin_no, '---') }}</td>
        </tr>
        <tr>
            <td class="label">RESIDENTIAL ADDRESS</td>
            <td colspan="3" class="value">
                {{ $employee->pdsPersonal->res_house_no ?? '' }} {{ $employee->pdsPersonal->res_street ?? '' }},
                {{ $employee->pdsPersonal->res_subdivision ?? '' }}, {{ $employee->pdsPersonal->res_barangay ?? '' }},
                {{ $employee->pdsPersonal->res_city ?? '' }}, {{ $employee->pdsPersonal->res_province ?? '' }}
                (Zip Code: {{ $employee->pdsPersonal->res_zip_code ?? '' }})
            </td>
        </tr>
        <tr>
            <td class="label">PERMANENT ADDRESS</td>
            <td colspan="3" class="value">
                {{ $employee->pdsPersonal->perm_house_no ?? '' }} {{ $employee->pdsPersonal->perm_street ?? '' }},
                {{ $employee->pdsPersonal->perm_subdivision ?? '' }}, {{ $employee->pdsPersonal->perm_barangay ?? '' }},
                {{ $employee->pdsPersonal->perm_city ?? '' }}, {{ $employee->pdsPersonal->perm_province ?? '' }}
                (Zip Code: {{ $employee->pdsPersonal->perm_zip_code ?? '' }})
            </td>
        </tr>
        <tr>
            <td class="label">TELEPHONE NO.</td>
            <td class="value">{{ $employee->pdsPersonal->telephone_no ?? '---' }}</td>
            <td class="label">MOBILE NO.</td>
            <td class="value">{{ $employee->pdsPersonal->mobile_no ?? '---' }}</td>
        </tr>
        <tr>
            <td class="label">E-MAIL ADDRESS</td>
            <td colspan="3" class="value">{{ $employee->pdsPersonal->email_address ?? $employee->user->email }}</td>
        </tr>
    </table>

    <!-- II. FAMILY BACKGROUND -->
    <table>
        <tr>
            <td colspan="4" class="section-header">II. FAMILY BACKGROUND</td>
        </tr>
        <tr>
            <td class="label">SPOUSE'S SURNAME</td>
            <td colspan="3" class="value">{{ $employee->pdsFamily->spouse_surname ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">FATHER'S SURNAME</td>
            <td colspan="3" class="value">{{ $employee->pdsFamily->father_surname ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">MOTHER'S MAIDEN NAME</td>
            <td colspan="3" class="value">{{ $employee->pdsFamily->mother_maiden_surname ?? 'N/A' }}</td>
        </tr>
    </table>

    @if($employee->pdsChildren->count() > 0)
    <table>
        <tr class="multi-row">
            <th style="width: 70%;">NAME OF CHILDREN</th>
            <th style="width: 30%;">DATE OF BIRTH</th>
        </tr>
        @foreach($employee->pdsChildren as $child)
        <tr>
            <td>{{ $child->fullname }}</td>
            <td>{{ $child->date_of_birth }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <!-- III. EDUCATIONAL BACKGROUND -->
    <table>
        <tr>
            <td colspan="5" class="section-header">III. EDUCATIONAL BACKGROUND</td>
        </tr>
        <tr class="multi-row">
            <th>LEVEL</th>
            <th>NAME OF SCHOOL</th>
            <th>BASIC EDUCATION/DEGREE/COURSE</th>
            <th>PERIOD FROM - TO</th>
            <th>SCHOLARSHIP/HONORS RECEIVED</th>
        </tr>
        @foreach($employee->pdsEducation as $edu)
        <tr>
            <td>{{ $edu->level }}</td>
            <td>{{ $edu->school_name }}</td>
            <td>{{ $edu->course }}</td>
            <td>{{ $edu->period_from }} - {{ $edu->period_to }}</td>
            <td>{{ $edu->honors ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </table>

    <!-- IV. CIVIL SERVICE ELIGIBILITY -->
    <table>
        <tr>
            <td colspan="4" class="section-header">IV. CIVIL SERVICE ELIGIBILITY</td>
        </tr>
        <tr class="multi-row">
            <th>TITLE</th>
            <th>RATING</th>
            <th>DATE OF EXAMINATION</th>
            <th>LICENSE NUMBER / VALIDITY</th>
        </tr>
        @foreach($employee->pdsEligibilities as $eli)
        <tr>
            <td>{{ $eli->title }}</td>
            <td>{{ $eli->rating }}</td>
            <td>{{ $eli->date_of_exam }}</td>
            <td>{{ $eli->license_number }} / {{ $eli->license_validity }}</td>
        </tr>
        @endforeach
    </table>

    <!-- V. WORK EXPERIENCE -->
    <table>
        <tr>
            <td colspan="5" class="section-header">V. WORK EXPERIENCE</td>
        </tr>
        <tr class="multi-row">
            <th>INCLUSIVE DATES</th>
            <th>POSITION TITLE</th>
            <th>DEPARTMENT / AGENCY / COMPANY</th>
            <th>MONTHLY SALARY</th>
            <th>STATUS OF APPOINTMENT</th>
        </tr>
        @foreach($employee->pdsWorkExperiences as $work)
        <tr>
            <td>{{ $work->date_from }} - {{ $work->date_to }}</td>
            <td>{{ $work->position_title }}</td>
            <td>{{ $work->company }}</td>
            <td>{{ $work->monthly_salary }}</td>
            <td>{{ $work->appointment_status }}</td>
        </tr>
        @endforeach
    </table>

    <!-- VI. VOLUNTARY WORK -->
    @if($employee->pdsVoluntaryWorks->count() > 0)
    <table>
        <tr>
            <td colspan="4" class="section-header">VI. VOLUNTARY WORK</td>
        </tr>
        <tr class="multi-row">
            <th>NAME & ADDRESS OF ORGANIZATION</th>
            <th>INCLUSIVE DATES</th>
            <th>NUMBER OF HOURS</th>
            <th>POSITION / NATURE OF WORK</th>
        </tr>
        @foreach($employee->pdsVoluntaryWorks as $vol)
        <tr>
            <td>{{ $vol->organization_name }}</td>
            <td>{{ $vol->date_from }} - {{ $vol->date_to }}</td>
            <td>{{ $vol->number_of_hours }}</td>
            <td>{{ $vol->position }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <!-- VII. LEARNING AND DEVELOPMENT (TRAINING) -->
    <table>
        <tr>
            <td colspan="4" class="section-header">VII. LEARNING AND DEVELOPMENT (TRAINING)</td>
        </tr>
        <tr class="multi-row">
            <th>TITLE OF LEARNING AND DEVELOPMENT INTERVENTIONS/TRAINING PROGRAMS</th>
            <th>INCLUSIVE DATES</th>
            <th>NUMBER OF HOURS</th>
            <th>CONDUCTED/SPONSORED BY</th>
        </tr>
        @foreach($employee->pdsTrainings as $train)
        <tr>
            <td>{{ $train->title }}</td>
            <td>{{ $train->date_from }} - {{ $train->date_to }}</td>
            <td>{{ $train->number_of_hours }}</td>
            <td>{{ $train->conducted_by }}</td>
        </tr>
        @endforeach
    </table>

    <!-- VIII. OTHER INFORMATION -->
    <table>
        <tr>
            <td colspan="3" class="section-header">VIII. OTHER INFORMATION</td>
        </tr>
        <tr class="multi-row">
            <th>SPECIAL SKILLS AND HOBBIES</th>
            <th>NON-ACADEMIC DISTINCTIONS / RECOGNITION</th>
            <th>MEMBERSHIP IN ASSOCIATION/ORGANIZATION</th>
        </tr>
        <tr>
            <td>
                @foreach($employee->pdsOthers->where('type', 'Skill') as $item)
                    • {{ $item->description }}<br>
                @endforeach
            </td>
            <td>
                @foreach($employee->pdsOthers->where('type', 'Distinction') as $item)
                    • {{ $item->description }}<br>
                @endforeach
            </td>
            <td>
                @foreach($employee->pdsOthers->where('type', 'Membership') as $item)
                    • {{ $item->description }}<br>
                @endforeach
            </td>
        </tr>
    </table>

    <!-- REFERENCES -->
    <table>
        <tr>
            <td colspan="3" class="section-header">REFERENCES</td>
        </tr>
        <tr class="multi-row">
            <th>NAME</th>
            <th>ADDRESS</th>
            <th>TEL. NO.</th>
        </tr>
        @foreach($employee->pdsReferences as $ref)
        <tr>
            <td>{{ $ref->name }}</td>
            <td>{{ $ref->address }}</td>
            <td>{{ $ref->telephone_no }}</td>
        </tr>
        @endforeach
    </table>

    <div style="margin-top: 50px;">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 50%;">
                    I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct and complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines.
                </td>
                <td style="border: none; width: 50%; text-align: center;">
                    <div style="border-bottom: 1px solid #000; width: 80%; margin: 0 auto; margin-top: 40px;"></div>
                    (Signature (Sign over Printed Name))
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
