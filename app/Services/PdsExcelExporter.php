<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdsExcelExporter
{
    public function download(Employee $employee): BinaryFileResponse
    {
        if (! class_exists(IOFactory::class)) {
            throw new RuntimeException('Excel library is not installed.');
        }

        $template = resource_path('templates/cs-form-212-revised-2025.xlsx');

        if (! File::exists($template)) {
            throw new RuntimeException('PDS Excel template is missing at resources/templates/cs-form-212-revised-2025.xlsx.');
        }

        $employee->loadMissing([
            'user',
            'pdsPersonal',
            'pdsFamily',
            'pdsChildren',
            'pdsEducation',
            'pdsEligibilities',
            'pdsWorkExperiences',
            'pdsVoluntaryWorks',
            'pdsTrainings',
            'pdsOthers',
            'pdsQuestionnaire',
            'pdsReferences',
            'pdsGovId',
        ]);

        $errorReporting = error_reporting();
        error_reporting($errorReporting & ~E_DEPRECATED);

        try {
            $spreadsheet = IOFactory::load($template);
        } finally {
            error_reporting($errorReporting);
        }

        $this->fillPersonalAndFamily($spreadsheet->getSheetByName('C1'), $employee);
        $this->fillEligibilityAndWork($spreadsheet->getSheetByName('C2'), $employee);
        $this->fillVoluntaryTrainingOther($spreadsheet->getSheetByName('C3'), $employee);
        $this->fillQuestionsReferencesDeclaration($spreadsheet->getSheetByName('C4'), $employee);

        $spreadsheet->setActiveSheetIndex(0);

        $directory = storage_path('app/generated/pds');
        File::ensureDirectoryExists($directory);

        $name = trim(($employee->lastname ?? '').'-'.($employee->firstname ?? 'employee'));
        $name = str($name)->lower()->replaceMatches('/[^a-z0-9]+/', '-')->trim('-')->toString() ?: 'employee';
        $path = $directory.'/pds-'.$name.'-'.now()->format('Ymd-His').'.xlsx';

        $errorReporting = error_reporting();
        error_reporting($errorReporting & ~E_DEPRECATED);

        try {
            (new Xlsx($spreadsheet))->save($path);
        } finally {
            error_reporting($errorReporting);
        }

        return response()
            ->download($path, 'PDS-'.$name.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    private function fillPersonalAndFamily(?Worksheet $sheet, Employee $employee): void
    {
        if (! $sheet) {
            return;
        }

        $p = $employee->pdsPersonal;
        $f = $employee->pdsFamily;

        $this->set($sheet, 'D10:N10', $p?->surname);
        $this->set($sheet, 'D11:K11', $p?->firstname);
        $this->set($sheet, 'D12:K12', $p?->middlename);
        $this->set($sheet, 'M12:N12', $p?->name_extension);
        $this->set($sheet, 'D13:F13', $this->date($p?->date_of_birth));
        $this->set($sheet, 'D15:F15', $p?->place_of_birth);
        // The official Excel template uses positioned checkbox objects in these areas.
        // Do not write text into the merged cells or it will overlap the printed checkbox labels.
        $this->set($sheet, 'D22:F23', $p?->height_m);
        $this->set($sheet, 'D24:F24', $p?->weight_kg);
        $this->set($sheet, 'D25:F26', $p?->blood_type);
        $this->set($sheet, 'D27:F28', $p?->umid_no);
        $this->set($sheet, 'D29:F30', $p?->pagibig_id_no);
        $this->set($sheet, 'D31:F31', $p?->philhealth_no);
        $this->set($sheet, 'D32:F32', $p?->philsys_no);
        $this->set($sheet, 'D33:F33', $p?->tin_no);
        $this->set($sheet, 'D34:F34', $p?->agency_employee_no);

        $this->set($sheet, 'L15:N15', $p?->citizenship_country);

        $this->set($sheet, 'I18:K18', $p?->res_house_no);
        $this->set($sheet, 'L18:N18', $p?->res_street);
        $this->set($sheet, 'I21:K21', $p?->res_subdivision);
        $this->set($sheet, 'L21:N21', $p?->res_barangay);
        $this->set($sheet, 'I23:K23', $p?->res_city);
        $this->set($sheet, 'L23:N23', $p?->res_province);
        $this->set($sheet, 'I24:N24', $p?->res_zip_code);

        $this->set($sheet, 'I26:K26', $p?->perm_house_no);
        $this->set($sheet, 'L26:N26', $p?->perm_street);
        $this->set($sheet, 'I28:K28', $p?->perm_subdivision);
        $this->set($sheet, 'L28:N28', $p?->perm_barangay);
        $this->set($sheet, 'I30:K30', $p?->perm_city);
        $this->set($sheet, 'L30:N30', $p?->perm_province);
        $this->set($sheet, 'I31:K31', $p?->perm_zip_code);
        $this->set($sheet, 'I32:N32', $p?->telephone_no);
        $this->set($sheet, 'I33:N33', $p?->mobile_no);
        $this->set($sheet, 'I34:N34', $p?->email_address);

        $this->set($sheet, 'D36:H36', $f?->spouse_surname);
        $this->set($sheet, 'D37:F37', $f?->spouse_firstname);
        $this->set($sheet, 'G37:H37', $f?->spouse_extension);
        $this->set($sheet, 'D38:H38', $f?->spouse_middlename);
        $this->set($sheet, 'D39:H39', $f?->spouse_occupation);
        $this->set($sheet, 'D40:H40', $f?->spouse_employer);
        $this->set($sheet, 'D41:H41', $f?->spouse_business_address);
        $this->set($sheet, 'D42:H42', $f?->spouse_telephone_no);

        $this->set($sheet, 'D43:H43', $f?->father_surname);
        $this->set($sheet, 'D44:F44', $f?->father_firstname);
        $this->set($sheet, 'G44:H44', $f?->father_extension);
        $this->set($sheet, 'D45:H45', $f?->father_middlename);
        $this->set($sheet, 'D46:H46', $f?->mother_maiden_surname);
        $this->set($sheet, 'D47:H47', $f?->mother_firstname);
        $this->set($sheet, 'D48:H48', $f?->mother_middlename);

        foreach ($employee->pdsChildren->take(12)->values() as $index => $child) {
            $row = 37 + $index;
            $this->set($sheet, "I{$row}:L{$row}", $child->fullname);
            $this->set($sheet, "M{$row}:N{$row}", $this->date($child->date_of_birth));
        }

        $educationRows = $this->educationRows($employee);
        foreach ($educationRows as $index => $education) {
            $row = 54 + $index;
            $this->set($sheet, "D{$row}:F{$row}", $education?->school_name);
            $this->set($sheet, "G{$row}:I{$row}", $education?->course);
            $this->set($sheet, "J{$row}", $education?->period_from);
            $this->set($sheet, "K{$row}", $education?->period_to);
            $this->set($sheet, "L{$row}", $education?->highest_level);
            $this->set($sheet, "M{$row}", $education?->year_graduated);
            $this->set($sheet, "N{$row}", $education?->honors);
        }

        $this->set($sheet, 'J60:K60', now()->format('d/m/Y'));
    }

    private function fillEligibilityAndWork(?Worksheet $sheet, Employee $employee): void
    {
        if (! $sheet) {
            return;
        }

        foreach ($employee->pdsEligibilities->take(7)->values() as $index => $eligibility) {
            $row = 5 + $index;
            $this->set($sheet, "A{$row}:E{$row}", $eligibility->title);
            $this->set($sheet, "F{$row}", $eligibility->rating);
            $this->set($sheet, "G{$row}:H{$row}", $this->date($eligibility->date_of_exam));
            $this->set($sheet, "I{$row}", $eligibility->place_of_exam);
            $this->set($sheet, "J{$row}", $eligibility->license_number);
            $this->set($sheet, "K{$row}", $this->date($eligibility->license_validity));
        }

        foreach ($employee->pdsWorkExperiences->take(28)->values() as $index => $work) {
            $row = 18 + $index;
            $this->set($sheet, "A{$row}:B{$row}", $this->date($work->date_from));
            $this->set($sheet, "C{$row}", $work->date_to ? $this->date($work->date_to) : 'PRESENT');
            $this->set($sheet, "D{$row}:F{$row}", $work->position_title);
            $this->set($sheet, "G{$row}:I{$row}", $work->company);
            $this->set($sheet, "J{$row}", $work->appointment_status);
            $this->set($sheet, "K{$row}", $work->is_gov_service ? 'Y' : 'N');
        }

        $this->set($sheet, 'I47:L47', now()->format('d/m/Y'));
    }

    private function fillVoluntaryTrainingOther(?Worksheet $sheet, Employee $employee): void
    {
        if (! $sheet) {
            return;
        }

        foreach ($employee->pdsVoluntaryWorks->take(7)->values() as $index => $voluntary) {
            $row = 6 + $index;
            $this->set($sheet, "A{$row}:D{$row}", $voluntary->organization_name);
            $this->set($sheet, "E{$row}", $this->date($voluntary->date_from));
            $this->set($sheet, "F{$row}", $this->date($voluntary->date_to));
            $this->set($sheet, "G{$row}", $voluntary->number_of_hours);
            $this->set($sheet, "H{$row}:M{$row}", $voluntary->position);
        }

        foreach ($employee->pdsTrainings->take(21)->values() as $index => $training) {
            $row = 18 + $index;
            $this->set($sheet, "A{$row}:D{$row}", $training->title);
            $this->set($sheet, "E{$row}", $this->date($training->date_from));
            $this->set($sheet, "F{$row}", $this->date($training->date_to));
            $this->set($sheet, "G{$row}", $training->number_of_hours);
            $this->set($sheet, "H{$row}", $training->type);
            $this->set($sheet, "I{$row}:M{$row}", $training->conducted_by);
        }

        foreach ($employee->pdsOthers->where('type', 'Skill')->take(7)->values() as $index => $other) {
            $this->set($sheet, 'B'.(42 + $index), $other->description);
        }

        foreach ($employee->pdsOthers->where('type', 'Distinction')->take(7)->values() as $index => $other) {
            $this->set($sheet, 'D'.(42 + $index).':H'.(42 + $index), $other->description);
        }

        foreach ($employee->pdsOthers->where('type', 'Membership')->take(7)->values() as $index => $other) {
            $this->set($sheet, 'J'.(42 + $index).':M'.(42 + $index), $other->description);
        }

        $this->set($sheet, 'G50:I50', now()->format('d/m/Y'));
    }

    private function fillQuestionsReferencesDeclaration(?Worksheet $sheet, Employee $employee): void
    {
        if (! $sheet) {
            return;
        }

        $q = $employee->pdsQuestionnaire;
        $gov = $employee->pdsGovId;

        $this->set($sheet, 'G10:L10', $q?->q34_details);
        $this->set($sheet, 'G14:L14', $q?->q35_details);
        $this->set($sheet, 'G19:L19', $q?->q35_details);
        $this->set($sheet, 'H20:I20', $this->date($q?->q35_date_filed));
        $this->set($sheet, 'G21:I21', $q?->q35_status);
        $this->set($sheet, 'G24:L24', $q?->q36_details);
        $this->set($sheet, 'G28:L28', $q?->q37_details);
        $this->set($sheet, 'G32:J32', $q?->q38_a_details);
        $this->set($sheet, 'G35:J35', $q?->q38_b_details);
        $this->set($sheet, 'G38:M38', $q?->q39_details);
        $this->set($sheet, 'G44:I44', $q?->q40_a_details);
        $this->set($sheet, 'G46:I46', $q?->q40_b_details);
        $this->set($sheet, 'G48:I48', $q?->q40_c_details);

        foreach ($employee->pdsReferences->take(3)->values() as $index => $reference) {
            $row = 52 + $index;
            $this->set($sheet, "A{$row}:E{$row}", $reference->name);
            $this->set($sheet, "F{$row}", $reference->address);
            $this->set($sheet, "G{$row}:I{$row}", $reference->telephone_no);
        }

        $this->set($sheet, 'D61', $gov?->id_type);
        $this->set($sheet, 'D62:D63', $gov?->id_no);
        $this->set($sheet, 'D64:D65', $gov?->date_place_issuance);
        $this->set($sheet, 'F65:I65', now()->format('d/m/Y'));
    }

    private function educationRows(Employee $employee): array
    {
        $levels = ['elementary', 'secondary', 'vocational', 'college', 'graduate'];

        return collect($levels)
            ->map(function (string $level) use ($employee) {
                return $employee->pdsEducation->first(function ($item) use ($level) {
                    $key = strtolower(trim((string) $item->level));

                    return str_contains($key, $level)
                        || ($level === 'secondary' && str_contains($key, 'high'))
                        || ($level === 'graduate' && (str_contains($key, 'master') || str_contains($key, 'doctor')));
                });
            })
            ->all();
    }

    private function set(Worksheet $sheet, string $cellOrRange, mixed $value): void
    {
        $cell = str_contains($cellOrRange, ':')
            ? explode(':', $cellOrRange, 2)[0]
            : $cellOrRange;

        $sheet->setCellValueExplicit($cell, $this->text($value), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($cell)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    }

    private function text(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function date(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

}
