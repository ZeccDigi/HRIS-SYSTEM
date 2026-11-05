<?php

namespace App\Services\Pds;

use App\Models\Employee;
use App\Models\PdsEducation;
use App\Support\PdsFormatter;
use Illuminate\Http\Response;
use setasign\Fpdi\Tcpdf\Fpdi;

class Cs212PdfGenerator
{
    private const A4_WIDTH_MM = 210;
    private const A4_HEIGHT_MM = 297;

    private ?int $templatePageCount = null;

    public function printPage1(Employee $employee, bool $showGuide = false): Response
    {
        $employee->loadMissing(['user', 'pdsPersonal']);

        $pdf = $this->buildPage1Calibration($employee, $showGuide);
        $personal = $employee->pdsPersonal;
        $surname = $personal?->surname ?? $employee->lastname;
        $filename = 'PDS_PAGE_1_'.preg_replace('/\s+/', '_', $surname).'_'.now()->format('Y-m-d').'.pdf';

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function buildPage1Calibration(Employee $employee, bool $showGuide = false): Fpdi
    {
        $templatePath = resource_path('pdf/cs_form_212_template.pdf');
        if (! file_exists($templatePath)) {
            throw new \RuntimeException("PDS template missing: {$templatePath}");
        }

        $pdf = new Fpdi('P', 'mm', [self::A4_WIDTH_MM, self::A4_HEIGHT_MM], true, 'UTF-8', false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('HRIS');
        $pdf->SetAuthor('HRIS');
        $pdf->SetTitle('Personal Data Sheet Page 1');

        $pdf->setSourceFile($templatePath);
        $pdf->AddPage('P', [self::A4_WIDTH_MM, self::A4_HEIGHT_MM]);
        $template = $pdf->importPage(1);
        $pdf->useTemplate($template, 0, 0, self::A4_WIDTH_MM, self::A4_HEIGHT_MM);

        if ($showGuide) {
            $this->drawCoordinateGuide($pdf);
        }

        $this->fillPage1StarterFields($pdf, $employee);

        return $pdf;
    }

    private function fillPage1StarterFields(Fpdi $pdf, Employee $employee): void
    {
        $p = $employee->pdsPersonal;

        /*
         * Page 1 calibration map in millimeters.
         * To adjust a field: open /pds/print?guide=1, read the grid,
         * then change x to move left/right and y to move up/down.
         * Keep this starter map small until these five fields print perfectly.
         */
        $fields = [
            'surname' => ['x' => 43.0, 'y' => 34.2, 'w' => 111.0],
            'first_name' => ['x' => 43.0, 'y' => 39.4, 'w' => 95.0],
            'middle_name' => ['x' => 43.0, 'y' => 44.7, 'w' => 111.0],
            'date_of_birth' => ['x' => 43.0, 'y' => 51.6, 'w' => 42.0],
        ];

        $this->writeTextMm($pdf, $fields['surname'], PdsFormatter::val($p?->surname ?? $employee->lastname));
        $this->writeTextMm($pdf, $fields['first_name'], PdsFormatter::val($p?->firstname ?? $employee->firstname));
        $this->writeTextMm($pdf, $fields['middle_name'], PdsFormatter::val($p?->middlename ?? $employee->middlename));
        $this->writeTextMm($pdf, $fields['date_of_birth'], PdsFormatter::date($p?->date_of_birth), false);
        $this->markSexMm($pdf, PdsFormatter::val($p?->sex));
    }

    private function writeTextMm(Fpdi $pdf, array $box, string $text, bool $uppercase = true, float $fontSize = 7.0): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $pdf->SetFont('helvetica', '', $fontSize);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($box['x'], $box['y']);
        $pdf->Cell($box['w'], $box['h'] ?? 4.2, $uppercase ? mb_strtoupper($text) : $text, 0, 0, 'L', false, '', 1);
    }

    private function writeMultiTextMm(Fpdi $pdf, array $box, string $text, bool $uppercase = true, float $fontSize = 6.5): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $pdf->SetFont('helvetica', '', $fontSize);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($box['x'], $box['y']);
        $pdf->MultiCell($box['w'], $box['line_h'] ?? 3.2, $uppercase ? mb_strtoupper($text) : $text, 0, 'L', false, 1, '', '', true, 0, false, true, $box['h'] ?? 8, 'M');
    }

    private function markCheckMm(Fpdi $pdf, float $x, float $y): void
    {
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y);
        $pdf->Cell(3, 3, 'X', 0, 0, 'C');
    }

    private function markSexMm(Fpdi $pdf, string $sex): void
    {
        $value = strtolower($sex);
        if (str_contains($value, 'female')) {
            $this->markCheckMm($pdf, 62.2, 84.0);
        } elseif (str_contains($value, 'male')) {
            $this->markCheckMm($pdf, 43.0, 84.0);
        }
    }

    private function drawCoordinateGuide(Fpdi $pdf): void
    {
        $pdf->SetDrawColor(255, 0, 0);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->SetFont('helvetica', '', 4);

        for ($x = 0; $x <= self::A4_WIDTH_MM; $x += 10) {
            $pdf->Line($x, 0, $x, self::A4_HEIGHT_MM);
            if ($x > 0) {
                $pdf->Text($x + 0.5, 2, (string) $x);
            }
        }

        for ($y = 0; $y <= self::A4_HEIGHT_MM; $y += 10) {
            $pdf->Line(0, $y, self::A4_WIDTH_MM, $y);
            if ($y > 0) {
                $pdf->Text(1, $y + 1.5, (string) $y);
            }
        }

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetTextColor(0, 0, 0);
    }

    public function download(Employee $employee, string $disposition = 'attachment'): Response
    {
        $employee->loadMissing([
            'user',
            'pdsPersonal', 'pdsFamily', 'pdsChildren', 'pdsEducation',
            'pdsEligibilities', 'pdsWorkExperiences', 'pdsVoluntaryWorks',
            'pdsTrainings', 'pdsOthers', 'pdsQuestionnaire', 'pdsReferences', 'pdsGovId',
        ]);

        $pdf = $this->build($employee);
        $personal = $employee->pdsPersonal;
        $surname = $personal?->surname ?? $employee->lastname;
        $filename = 'PDS_CS212_'.preg_replace('/\s+/', '_', $surname).'_'.now()->format('Y-m-d').'.pdf';

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    public function build(Employee $employee): Fpdi
    {
        $cfg = config('cs212');
        [$w, $h] = $cfg['page_size'];

        $pdf = new Fpdi('P', 'pt', [$w, $h]);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $usePdfTemplate = $this->bindTemplate($pdf);

        $pdf->AddPage();
        if ($usePdfTemplate) {
            $pdf->useTemplate($pdf->importPage(1), 0, 0, $w, $h);
        } else {
            $this->drawPageBackground($pdf, 1, $w, $h);
        }
        $this->fillPage1($pdf, $employee, $cfg);

        $pdf->AddPage();
        if ($usePdfTemplate) {
            $pdf->useTemplate($pdf->importPage(2), 0, 0, $w, $h);
        } else {
            $this->drawPageBackground($pdf, 2, $w, $h);
        }
        $this->fillPage2($pdf, $employee, $cfg);

        $pdf->AddPage();
        if ($usePdfTemplate) {
            $pdf->useTemplate($pdf->importPage(3), 0, 0, $w, $h);
        } else {
            $this->drawPageBackground($pdf, 3, $w, $h);
        }
        $this->fillPage3($pdf, $employee, $cfg);

        $pdf->AddPage();
        if ($usePdfTemplate) {
            $pdf->useTemplate($pdf->importPage(4), 0, 0, $w, $h);
        } else {
            $this->drawPageBackground($pdf, 4, $w, $h);
        }
        $this->fillPage4($pdf, $employee, $cfg);

        return $pdf;
    }

    /**
     * Import official CS Form 212 PDF background (must be PDF 1.4 compatible for FPDI).
     * Falls back to PNG page images when the template uses unsupported compression.
     */
    private function bindTemplate(Fpdi $pdf): bool
    {
        $pngPages = collect(range(1, 4))->every(function (int $page) {
            return file_exists(public_path("images/pds/pds-page-{$page}.png"));
        });

        if ($pngPages) {
            return false;
        }

        $path = resource_path('pdf/cs_form_212_template.pdf');

        try {
            $this->templatePageCount = $pdf->setSourceFile($path);

            return $this->templatePageCount >= 4;
        } catch (\Throwable) {
            $this->templatePageCount = null;

            return false;
        }
    }

    private function drawPageBackground(Fpdi $pdf, int $page, float $w, float $h): void
    {
        $image = public_path("images/pds/pds-page-{$page}.png");

        if (! file_exists($image)) {
            throw new \RuntimeException("PDS page background missing: {$image}");
        }

        $pdf->Image($image, 0, 0, $w, $h, '', '', '', false, 300, '', false, false, 0);
    }

    private function initText(Fpdi $pdf, float $size = 0): void
    {
        $pdf->SetFont('helvetica', '', $size ?: config('cs212.font_size', 7));
        $pdf->SetTextColor(0, 0, 0);
    }

    private function writeAt(Fpdi $pdf, float $x, float $y, float $w, string $text): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $this->initText($pdf, $this->fitFontSize($pdf, $text, $w));
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 8, $text, 0, 0, 'L', false, '', 1, false, 'T', 'M');
    }

    private function fillPage1(Fpdi $pdf, Employee $employee, array $cfg): void
    {
        $p = $employee->pdsPersonal;
        $f = $employee->pdsFamily;
        $m = $cfg['page1'];

        $this->writeAt($pdf, $m['surname']['x'], $m['surname']['y'], $m['surname']['w'], PdsFormatter::val($p?->surname ?? $employee->lastname));
        $this->writeAt($pdf, $m['first_name']['x'], $m['first_name']['y'], $m['first_name']['w'], PdsFormatter::val($p?->firstname ?? $employee->firstname));
        $this->writeAt($pdf, $m['name_extension']['x'], $m['name_extension']['y'], $m['name_extension']['w'], PdsFormatter::val($p?->name_extension));
        $this->writeAt($pdf, $m['middle_name']['x'], $m['middle_name']['y'], $m['middle_name']['w'], PdsFormatter::val($p?->middlename ?? $employee->middlename));
        $this->writeAt($pdf, $m['date_of_birth']['x'], $m['date_of_birth']['y'], $m['date_of_birth']['w'], PdsFormatter::date($p?->date_of_birth));
        $this->writeAt($pdf, $m['place_of_birth']['x'], $m['place_of_birth']['y'], $m['place_of_birth']['w'], PdsFormatter::val($p?->place_of_birth));
        $this->markSex($pdf, PdsFormatter::val($p?->sex));
        $this->markCivilStatus($pdf, PdsFormatter::val($p?->civil_status));
        $this->writeAt($pdf, $m['height']['x'], $m['height']['y'], $m['height']['w'], PdsFormatter::val($p?->height_m));
        $this->writeAt($pdf, $m['weight']['x'], $m['weight']['y'], $m['weight']['w'], PdsFormatter::val($p?->weight_kg));
        $this->writeAt($pdf, $m['blood_type']['x'], $m['blood_type']['y'], $m['blood_type']['w'], PdsFormatter::val($p?->blood_type));
        $this->writeAt($pdf, $m['gsis']['x'], $m['gsis']['y'], $m['gsis']['w'], PdsFormatter::val($p?->gsis_id_no));
        $this->writeAt($pdf, $m['pagibig']['x'], $m['pagibig']['y'], $m['pagibig']['w'], PdsFormatter::pagibigIdNo($p?->pagibig_id_no));
        $this->writeAt($pdf, $m['philhealth']['x'], $m['philhealth']['y'], $m['philhealth']['w'], PdsFormatter::philhealthNo($p?->philhealth_no));
        $this->writeAt($pdf, $m['sss']['x'], $m['sss']['y'], $m['sss']['w'], PdsFormatter::sssNo($p?->sss_no));
        $this->writeAt($pdf, $m['tin']['x'], $m['tin']['y'], $m['tin']['w'], PdsFormatter::tinNo($p?->tin_no));
        $this->writeAt($pdf, $m['agency_employee']['x'], $m['agency_employee']['y'], $m['agency_employee']['w'], PdsFormatter::val($p?->agency_employee_no));

        $citizenship = PdsFormatter::val($p?->citizenship, 'Filipino');
        if ($p?->citizenship_type) {
            $citizenship .= ' — '.$p->citizenship_type;
        }
        if ($p?->citizenship_country) {
            $citizenship .= ' ('.$p->citizenship_country.')';
        }
        $this->markCitizenship($pdf, PdsFormatter::val($p?->citizenship, 'Filipino'), PdsFormatter::val($p?->citizenship_type));
        if ($p?->citizenship_country) {
            $this->writeAt($pdf, 468, 220, 110, PdsFormatter::val($p->citizenship_country));
        }

        $this->writeAt($pdf, $m['res_house']['x'], $m['res_house']['y'], $m['res_house']['w'], PdsFormatter::val($p?->res_house_no));
        $this->writeAt($pdf, $m['res_street']['x'], $m['res_street']['y'], $m['res_street']['w'], PdsFormatter::val($p?->res_street));
        $this->writeAt($pdf, $m['res_subd']['x'], $m['res_subd']['y'], $m['res_subd']['w'], PdsFormatter::val($p?->res_subdivision));
        $this->writeAt($pdf, $m['res_brgy']['x'], $m['res_brgy']['y'], $m['res_brgy']['w'], PdsFormatter::val($p?->res_barangay));
        $this->writeAt($pdf, $m['res_city']['x'], $m['res_city']['y'], $m['res_city']['w'], PdsFormatter::val($p?->res_city));
        $this->writeAt($pdf, $m['res_province']['x'], $m['res_province']['y'], $m['res_province']['w'], PdsFormatter::val($p?->res_province));
        $this->writeAt($pdf, $m['res_zip']['x'], $m['res_zip']['y'], $m['res_zip']['w'], PdsFormatter::val($p?->res_zip_code));

        $this->writeAt($pdf, $m['perm_house']['x'], $m['perm_house']['y'], $m['perm_house']['w'], PdsFormatter::val($p?->perm_house_no));
        $this->writeAt($pdf, $m['perm_street']['x'], $m['perm_street']['y'], $m['perm_street']['w'], PdsFormatter::val($p?->perm_street));
        $this->writeAt($pdf, $m['perm_subd']['x'], $m['perm_subd']['y'], $m['perm_subd']['w'], PdsFormatter::val($p?->perm_subdivision));
        $this->writeAt($pdf, $m['perm_brgy']['x'], $m['perm_brgy']['y'], $m['perm_brgy']['w'], PdsFormatter::val($p?->perm_barangay));
        $this->writeAt($pdf, $m['perm_city']['x'], $m['perm_city']['y'], $m['perm_city']['w'], PdsFormatter::val($p?->perm_city));
        $this->writeAt($pdf, $m['perm_province']['x'], $m['perm_province']['y'], $m['perm_province']['w'], PdsFormatter::val($p?->perm_province));
        $this->writeAt($pdf, $m['perm_zip']['x'], $m['perm_zip']['y'], $m['perm_zip']['w'], PdsFormatter::val($p?->perm_zip_code));

        $this->writeAt($pdf, $m['telephone']['x'], $m['telephone']['y'], $m['telephone']['w'], PdsFormatter::val($p?->telephone_no));
        $this->writeAt($pdf, $m['mobile']['x'], $m['mobile']['y'], $m['mobile']['w'], PdsFormatter::val($p?->mobile_no ?? $employee->contact_number));
        $this->writeAt($pdf, $m['email']['x'], $m['email']['y'], $m['email']['w'], PdsFormatter::val($p?->email_address ?? $employee->user?->email));

        if ($f) {
            $this->writeAt($pdf, $m['spouse_surname']['x'], $m['spouse_surname']['y'], $m['spouse_surname']['w'], PdsFormatter::val($f->spouse_surname));
            $this->writeAt($pdf, $m['spouse_first']['x'], $m['spouse_first']['y'], $m['spouse_first']['w'], PdsFormatter::val($f->spouse_firstname));
            $this->writeAt($pdf, $m['spouse_middle']['x'], $m['spouse_middle']['y'], $m['spouse_middle']['w'], PdsFormatter::val($f->spouse_middlename));
            $this->writeAt($pdf, $m['spouse_occupation']['x'], $m['spouse_occupation']['y'], $m['spouse_occupation']['w'], PdsFormatter::val($f->spouse_occupation));
            $this->writeAt($pdf, $m['spouse_employer']['x'], $m['spouse_employer']['y'], $m['spouse_employer']['w'], PdsFormatter::val($f->spouse_employer));
            $this->writeAt($pdf, $m['spouse_tel']['x'], $m['spouse_tel']['y'], $m['spouse_tel']['w'], PdsFormatter::val($f->spouse_telephone_no));
            $this->writeAt($pdf, $m['father_surname']['x'], $m['father_surname']['y'], $m['father_surname']['w'], PdsFormatter::val($f->father_surname));
            $this->writeAt($pdf, $m['father_first']['x'], $m['father_first']['y'], $m['father_first']['w'], PdsFormatter::val($f->father_firstname));
            $this->writeAt($pdf, $m['father_middle']['x'], $m['father_middle']['y'], $m['father_middle']['w'], PdsFormatter::val($f->father_middlename));
            $this->writeAt($pdf, $m['mother_surname']['x'], $m['mother_surname']['y'], $m['mother_surname']['w'], PdsFormatter::val($f->mother_maiden_surname));
            $this->writeAt($pdf, $m['mother_first']['x'], $m['mother_first']['y'], $m['mother_first']['w'], PdsFormatter::val($f->mother_firstname));
            $this->writeAt($pdf, $m['mother_middle']['x'], $m['mother_middle']['y'], $m['mother_middle']['w'], PdsFormatter::val($f->mother_middlename));
        }

        $childCfg = $m['children'];
        $y = $childCfg['y'];
        foreach ($employee->pdsChildren->take($childCfg['max']) as $child) {
            $this->writeAt($pdf, $childCfg['x'], $y, $childCfg['w'] * 0.65, $child->fullname);
            $this->writeAt($pdf, $childCfg['x'] + $childCfg['w'] * 0.65, $y, $childCfg['w'] * 0.35, PdsFormatter::date($child->date_of_birth));
            $y += $childCfg['row_h'];
        }

        $eduCfg = $m['education'];
        $eduByLevel = $this->educationByLevel($employee);
        $levels = ['elementary', 'secondary', 'vocational', 'college', 'graduate'];
        $y = $eduCfg['y'];
        $cols = $eduCfg['cols'];
        foreach ($levels as $level) {
            $edu = $eduByLevel[$level] ?? null;
            if ($edu) {
                $this->writeAt($pdf, $cols['school'], $y, 110, PdsFormatter::val($edu->school_name));
                $this->writeAt($pdf, $cols['course'], $y, 126, PdsFormatter::val($edu->course));
                $this->writeAt($pdf, $cols['from'], $y, 50, PdsFormatter::val($edu->period_from));
                $this->writeAt($pdf, $cols['to'], $y, 36, PdsFormatter::val($edu->period_to));
                $this->writeAt($pdf, $cols['units'], $y, 38, PdsFormatter::val($edu->highest_level));
                $this->writeAt($pdf, $cols['year'], $y, 44, PdsFormatter::val($edu->year_graduated));
                $this->writeAt($pdf, $cols['honors'], $y, 38, PdsFormatter::val($edu->honors));
            }
            $y += $eduCfg['row_h'];
        }
    }

    private function fillPage2(Fpdi $pdf, Employee $employee, array $cfg): void
    {
        $eCfg = $cfg['page2']['eligibility'];
        $y = $eCfg['y'];
        $c = $eCfg['cols'];
        foreach ($employee->pdsEligibilities->take($eCfg['max']) as $eli) {
            $this->writeAt($pdf, $c['title'], $y, 85, $eli->title);
            $this->writeAt($pdf, $c['rating'], $y, 30, PdsFormatter::val($eli->rating));
            $this->writeAt($pdf, $c['date'], $y, 32, PdsFormatter::date($eli->date_of_exam));
            $this->writeAt($pdf, $c['place'], $y, 75, PdsFormatter::val($eli->place_of_exam));
            $this->writeAt($pdf, $c['number'], $y, 22, PdsFormatter::val($eli->license_number));
            $this->writeAt($pdf, $c['validity'], $y, 32, PdsFormatter::date($eli->license_validity));
            $y += $eCfg['row_h'];
        }

        $wCfg = $cfg['page2']['work'];
        $y = $wCfg['y'];
        $c = $wCfg['cols'];
        foreach ($employee->pdsWorkExperiences->take($wCfg['max']) as $work) {
            $this->writeAt($pdf, $c['from'], $y, 18, PdsFormatter::date($work->date_from));
            $this->writeAt($pdf, $c['to'], $y, 20, $work->date_to ? PdsFormatter::date($work->date_to) : 'PRESENT');
            $this->writeAt($pdf, $c['position'], $y, 72, $work->position_title);
            $this->writeAt($pdf, $c['company'], $y, 75, $work->company);
            $this->writeAt($pdf, $c['salary'], $y, 12, PdsFormatter::val($work->monthly_salary));
            $this->writeAt($pdf, $c['grade'], $y, 16, PdsFormatter::val($work->salary_grade));
            $this->writeAt($pdf, $c['status'], $y, 22, PdsFormatter::val($work->appointment_status));
            $this->writeAt($pdf, $c['gov'], $y, 12, $work->is_gov_service ? 'Y' : 'N');
            $y += $wCfg['row_h'];
        }
    }

    private function fillPage3(Fpdi $pdf, Employee $employee, array $cfg): void
    {
        $vCfg = $cfg['page3']['voluntary'];
        $y = $vCfg['y'];
        $c = $vCfg['cols'];
        foreach ($employee->pdsVoluntaryWorks->take($vCfg['max']) as $vol) {
            $this->writeAt($pdf, $c['org'], $y, 84, PdsFormatter::val($vol->organization_name));
            $this->writeAt($pdf, $c['from'], $y, 23, PdsFormatter::date($vol->date_from));
            $this->writeAt($pdf, $c['to'], $y, 23, PdsFormatter::date($vol->date_to));
            $this->writeAt($pdf, $c['hours'], $y, 22, PdsFormatter::val($vol->number_of_hours));
            $this->writeAt($pdf, $c['position'], $y, 372, PdsFormatter::val($vol->position));
            $y += $vCfg['row_h'];
        }

        $tCfg = $cfg['page3']['training'];
        $y = $tCfg['y'];
        $c = $tCfg['cols'];
        foreach ($employee->pdsTrainings->take($tCfg['max']) as $train) {
            $this->writeAt($pdf, $c['title'], $y, 84, PdsFormatter::val($train->title));
            $this->writeAt($pdf, $c['from'], $y, 23, PdsFormatter::date($train->date_from));
            $this->writeAt($pdf, $c['to'], $y, 23, PdsFormatter::date($train->date_to));
            $this->writeAt($pdf, $c['hours'], $y, 22, PdsFormatter::val($train->number_of_hours));
            $this->writeAt($pdf, $c['type'], $y, 26, PdsFormatter::val($train->type));
            $this->writeAt($pdf, $c['by'], $y, 345, PdsFormatter::val($train->conducted_by));
            $y += $tCfg['row_h'];
        }

        $skills = $employee->pdsOthers->where('type', 'Skill')->pluck('description')->implode('; ');
        $distinctions = $employee->pdsOthers->where('type', 'Distinction')->pluck('description')->implode('; ');
        $memberships = $employee->pdsOthers->where('type', 'Membership')->pluck('description')->implode('; ');
        $o = $cfg['page3'];
        $this->writeMulti($pdf, $o['other_skills']['x'], $o['other_skills']['y'], $o['other_skills']['w'], $skills, $o['other_skills']['h']);
        $this->writeMulti($pdf, $o['other_distinctions']['x'], $o['other_distinctions']['y'], $o['other_distinctions']['w'], $distinctions, $o['other_distinctions']['h']);
        $this->writeMulti($pdf, $o['other_membership']['x'], $o['other_membership']['y'], $o['other_membership']['w'], $memberships, $o['other_membership']['h']);
    }

    private function fillPage4(Fpdi $pdf, Employee $employee, array $cfg): void
    {
        $q = $employee->pdsQuestionnaire;
        $gov = $employee->pdsGovId;
        $p4 = $cfg['page4'];

        if ($q) {
            $answers = [
                ($q->q34_a || $q->q34_b) ? 'YES' : 'NO',
                ($q->q35_a || $q->q35_b) ? 'YES' : 'NO',
                $q->q36 ? 'YES' : 'NO',
                $q->q37 ? 'YES' : 'NO',
                ($q->q38_a || $q->q38_b) ? 'YES' : 'NO',
                $q->q39 ? 'YES' : 'NO',
                $q->q40_a ? 'YES' : 'NO',
                $q->q40_b ? 'YES' : 'NO',
                $q->q40_c ? 'YES' : 'NO',
            ];
            $y = $p4['questions']['y'];
            foreach ($answers as $ans) {
                $this->writeAt($pdf, $p4['questions']['x'], $y, 30, $ans);
                $y += $p4['questions']['row_h'];
            }
        }

        $rCfg = $p4['references'];
        $y = $rCfg['y'];
        $c = $rCfg['cols'];
        foreach ($employee->pdsReferences->take($rCfg['max']) as $ref) {
            $this->writeAt($pdf, $c['name'], $y, 105, PdsFormatter::val($ref->name));
            $this->writeAt($pdf, $c['address'], $y, 100, PdsFormatter::val($ref->address));
            $this->writeAt($pdf, $c['tel'], $y, 120, PdsFormatter::val($ref->telephone_no));
            $y += $rCfg['row_h'];
        }

        if ($gov) {
            $this->writeAt($pdf, $p4['gov_id_type']['x'], $p4['gov_id_type']['y'], $p4['gov_id_type']['w'], PdsFormatter::val($gov->id_type));
            $this->writeAt($pdf, $p4['gov_id_no']['x'], $p4['gov_id_no']['y'], $p4['gov_id_no']['w'], PdsFormatter::val($gov->id_no));
            $this->writeAt($pdf, $p4['gov_id_issuance']['x'], $p4['gov_id_issuance']['y'], $p4['gov_id_issuance']['w'], PdsFormatter::val($gov->date_place_issuance));
        }

        $this->writeAt($pdf, $p4['date_accomplished']['x'], $p4['date_accomplished']['y'], $p4['date_accomplished']['w'], now()->format('m/d/Y'));

        $photo = $employee->profile_picture_absolute_path;
        if ($photo && file_exists($photo)) {
            $ph = $p4['photo'];
            $pdf->Image($photo, $ph['x'], $ph['y'], $ph['w'], $ph['h']);
        }
    }

    private function writeMulti(Fpdi $pdf, float $x, float $y, float $w, string $text, ?float $h = null): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }
        $lineHeight = 7;
        $fontSize = $this->fitFontSize($pdf, $text, $w);
        if ($h !== null) {
            $fontSize = $this->fitFontSizeForBox($pdf, $text, $w, $h, $lineHeight, $fontSize);
            $text = $this->fitTextForBox($pdf, $text, $w, $h, $lineHeight);
        }

        $this->initText($pdf, $fontSize);
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($w, $lineHeight, $text, 0, 'L');
    }

    private function markCheckbox(Fpdi $pdf, float $x, float $y): void
    {
        $this->initText($pdf, 7);
        $pdf->SetXY($x, $y);
        $pdf->Cell(8, 8, 'X', 0, 0, 'C');
    }

    private function markSex(Fpdi $pdf, string $sex): void
    {
        $value = strtolower($sex);
        if (str_contains($value, 'male') && ! str_contains($value, 'female')) {
            $this->markCheckbox($pdf, 125, 286);
        } elseif (str_contains($value, 'female')) {
            $this->markCheckbox($pdf, 179, 286);
        }
    }

    private function markCivilStatus(Fpdi $pdf, string $status): void
    {
        $value = strtolower($status);
        if (str_contains($value, 'single')) {
            $this->markCheckbox($pdf, 125, 307);
        } elseif (str_contains($value, 'married')) {
            $this->markCheckbox($pdf, 179, 307);
        } elseif (str_contains($value, 'widow')) {
            $this->markCheckbox($pdf, 125, 321);
        } elseif (str_contains($value, 'separated')) {
            $this->markCheckbox($pdf, 179, 321);
        } elseif ($value !== '' && $value !== 'n/a') {
            $this->markCheckbox($pdf, 125, 335);
            $this->writeAt($pdf, 160, 335, 65, $status);
        }
    }

    private function markCitizenship(Fpdi $pdf, string $citizenship, string $type): void
    {
        $citizenshipValue = strtolower($citizenship);
        $typeValue = strtolower($type);

        if ($citizenshipValue === '' || str_contains($citizenshipValue, 'filipino')) {
            $this->markCheckbox($pdf, 369, 180);
        }

        if (str_contains($citizenshipValue, 'dual')) {
            $this->markCheckbox($pdf, 454, 180);
        }

        if (str_contains($typeValue, 'birth')) {
            $this->markCheckbox($pdf, 470, 195);
        } elseif (str_contains($typeValue, 'natural')) {
            $this->markCheckbox($pdf, 510, 195);
        }
    }

    private function fitFontSize(Fpdi $pdf, string $text, float $width): float
    {
        $baseSize = (float) config('cs212.font_size', 7);
        $minSize = 4.8;
        $size = $baseSize;

        $pdf->SetFont('helvetica', '', $size);
        while ($size > $minSize && $pdf->GetStringWidth($text) > ($width - 2)) {
            $size -= 0.25;
            $pdf->SetFont('helvetica', '', $size);
        }

        return max($size, $minSize);
    }

    private function fitFontSizeForBox(Fpdi $pdf, string $text, float $width, float $height, float $lineHeight, float $startSize): float
    {
        $minSize = 4.8;
        $size = $startSize;

        $pdf->SetFont('helvetica', '', $size);
        while ($size > $minSize && ($pdf->getNumLines($text, $width) * $lineHeight) > $height) {
            $size -= 0.25;
            $pdf->SetFont('helvetica', '', $size);
        }

        return max($size, $minSize);
    }

    private function fitTextForBox(Fpdi $pdf, string $text, float $width, float $height, float $lineHeight): string
    {
        if (($pdf->getNumLines($text, $width) * $lineHeight) <= $height) {
            return $text;
        }

        $words = preg_split('/\s+/', $text) ?: [];
        while (count($words) > 1) {
            array_pop($words);
            $candidate = implode(' ', $words).'...';
            if (($pdf->getNumLines($candidate, $width) * $lineHeight) <= $height) {
                return $candidate;
            }
        }

        return mb_substr($text, 0, 24).'...';
    }

    /** @return array<string, PdsEducation|null> */
    private function educationByLevel(Employee $employee): array
    {
        $map = [];
        foreach ($employee->pdsEducation as $edu) {
            $key = strtolower(trim($edu->level));
            if (str_contains($key, 'element')) {
                $map['elementary'] = $edu;
            } elseif (str_contains($key, 'second') || str_contains($key, 'high')) {
                $map['secondary'] = $edu;
            } elseif (str_contains($key, 'vocational') || str_contains($key, 'trade')) {
                $map['vocational'] = $edu;
            } elseif (str_contains($key, 'college') || str_contains($key, 'bachelor')) {
                $map['college'] = $edu;
            } elseif (str_contains($key, 'graduate') || str_contains($key, 'master') || str_contains($key, 'doctor')) {
                $map['graduate'] = $edu;
            } else {
                $map[$key] = $edu;
            }
        }

        return $map;
    }
}
