<?php

namespace App\Services;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Response;
use setasign\Fpdi\Tcpdf\Fpdi;

class LeaveFormPdfGenerator
{
    private const PAGE_WIDTH_PT = 816;
    private const PAGE_HEIGHT_PT = 1056;

    public function print(LeaveRequest $leaveRequest, bool $showGuide = false): Response
    {
        $leaveRequest->load(['employee.leaveCredits.leaveType', 'employee.user', 'leaveType', 'chief', 'hrstaff', 'regionalDirector']);

        $pdf = $this->build($leaveRequest, $showGuide);
        $employee = $leaveRequest->employee;
        $filename = 'LEAVE_FORM_'.preg_replace('/\s+/', '_', trim(($employee?->lastname ?? 'employee').'_'.$leaveRequest->id)).'.pdf';

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function build(LeaveRequest $leaveRequest, bool $showGuide = false): Fpdi
    {
        $template = resource_path('pdf/leave_form_template.pdf');
        if (! file_exists($template)) {
            throw new \RuntimeException("Leave form template missing: {$template}");
        }

        $pdf = new Fpdi('P', 'pt', [self::PAGE_WIDTH_PT, self::PAGE_HEIGHT_PT], true, 'UTF-8', false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('HRIS');
        $pdf->SetAuthor('HRIS');
        $pdf->SetTitle('Application for Leave');

        $pdf->setSourceFile($template);
        $pdf->AddPage('P', [self::PAGE_WIDTH_PT, self::PAGE_HEIGHT_PT]);
        $page = $pdf->importPage(1);
        $pdf->useTemplate($page, 0, 0, self::PAGE_WIDTH_PT, self::PAGE_HEIGHT_PT);

        if ($showGuide) {
            $this->drawCoordinateGuide($pdf);
        }

        $this->fill($pdf, $leaveRequest);

        return $pdf;
    }

    private function fill(Fpdi $pdf, LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;
        $leaveType = $leaveRequest->leaveType?->name ?? '';
        $year = Carbon::parse($leaveRequest->start_date)->year;
        $employee?->ensureLeaveCredits($year);
        $employee?->load('leaveCredits.leaveType');

        $vacationCredit = $employee?->leaveCredits->first(fn ($credit) => str_contains(strtolower($credit->leaveType?->name ?? ''), 'vacation'));
        $sickCredit = $employee?->leaveCredits->first(fn ($credit) => str_contains(strtolower($credit->leaveType?->name ?? ''), 'sick'));
        $isSick = str_contains(strtolower($leaveType), 'sick');
        $duration = (float) $leaveRequest->duration;
        $vacationCurrent = (float) ($vacationCredit?->balance ?? 0);
        $sickCurrent = (float) ($sickCredit?->balance ?? 0);
        $vacationLess = $isSick ? 0.0 : $duration;
        $sickLess = $isSick ? $duration : 0.0;

        $this->coverSampleValues($pdf);

        $this->write($pdf, 157, 174, 150, $employee?->division ?: 'DMW RO XI');
        $this->write($pdf, 395, 174, 100, $employee?->lastname);
        $this->write($pdf, 505, 174, 120, $employee?->firstname);
        $this->write($pdf, 650, 174, 75, $employee?->middlename);
        $this->write($pdf, 195, 202, 100, $this->date($leaveRequest->date_filed), false);
        $this->write($pdf, 455, 202, 120, $employee?->position ?: 'N/A');
        $this->write($pdf, 662, 202, 70, 'N/A');

        $this->markLeaveType($pdf, $leaveType);
        $this->writeLeaveDetails($pdf, $leaveRequest, $leaveType);
        $this->write($pdf, 218, 596, 70, (string) $leaveRequest->duration, false, 10, 'C');
        $this->write($pdf, 172, 642, 170, $this->inclusiveDates($leaveRequest), false, 9, 'C');
        $this->mark($pdf, 463, 622); // Commutation requested

        $this->write($pdf, 198, 730, 135, now()->format('F d, Y'), false, 8, 'C');
        $this->write($pdf, 230, 758, 70, number_format($vacationCurrent, 1), false, 7, 'C');
        $this->write($pdf, 330, 758, 70, number_format($sickCurrent, 1), false, 7, 'C');
        $this->write($pdf, 230, 773, 70, $vacationLess > 0 ? number_format($vacationLess, 1) : '', false, 7, 'C');
        $this->write($pdf, 330, 773, 70, $sickLess > 0 ? number_format($sickLess, 1) : '', false, 7, 'C');
        $this->write($pdf, 230, 789, 70, number_format($vacationCurrent - $vacationLess, 1), false, 7, 'C');
        $this->write($pdf, 330, 789, 70, number_format($sickCurrent - $sickLess, 1), false, 7, 'C');

        if ($leaveRequest->chief_status === 'approved') {
            $this->mark($pdf, 464, 728);
            $this->write($pdf, 560, 824, 120, $this->employeeName($leaveRequest->chief), false, 8, 'C');
        } elseif ($leaveRequest->chief_status === 'rejected') {
            $this->mark($pdf, 464, 748);
            $this->writeMulti($pdf, 584, 750, 130, 34, $leaveRequest->chief_remarks ?: 'Disapproved', false, 7);
        }

        if ($leaveRequest->status === 'approved') {
            $this->write($pdf, 122, 878, 40, $leaveRequest->is_paid ? (string) $leaveRequest->duration : '', false, 8, 'C');
            $this->write($pdf, 122, 893, 40, $leaveRequest->is_paid ? '' : (string) $leaveRequest->duration, false, 8, 'C');
        } elseif ($leaveRequest->status === 'rejected') {
            $this->writeMulti($pdf, 482, 878, 235, 45, $leaveRequest->rd_remarks ?: $leaveRequest->hrstaff_remarks ?: $leaveRequest->chief_remarks ?: 'Disapproved', false, 7);
        }
    }

    private function coverSampleValues(Fpdi $pdf): void
    {
        $pdf->SetFillColor(255, 255, 255);
        foreach ([
            [145, 164, 125, 22], [385, 164, 105, 22], [493, 164, 140, 22], [635, 164, 85, 22],
            [188, 193, 112, 18], [445, 193, 135, 18], [670, 193, 60, 18],
            [96, 362, 14, 18], [570, 288, 160, 24], [218, 586, 76, 22], [165, 632, 180, 24],
            [459, 288, 14, 18], [462, 618, 10, 10], [225, 752, 83, 54], [322, 752, 83, 54],
            [204, 815, 178, 22], [548, 815, 174, 22], [118, 868, 60, 55], [482, 868, 245, 60],
        ] as [$x, $y, $w, $h]) {
            $pdf->Rect($x, $y, $w, $h, 'F');
        }
    }

    private function markLeaveType(Fpdi $pdf, string $leaveType): void
    {
        $map = [
            'vacation' => [101, 278],
            'mandatory' => [101, 297],
            'force' => [101, 297],
            'sick' => [101, 316],
            'maternity' => [101, 335],
            'paternity' => [101, 354],
            'special privilege' => [101, 373],
            'solo parent' => [101, 392],
            'study' => [101, 411],
            'vawc' => [101, 431],
            'rehabilitation' => [101, 450],
            'women' => [101, 469],
            'emergency' => [101, 488],
            'calamity' => [101, 488],
            'adoption' => [101, 507],
        ];

        $lower = strtolower($leaveType);
        foreach ($map as $needle => [$x, $y]) {
            if (str_contains($lower, $needle)) {
                $this->mark($pdf, $x, $y);
                return;
            }
        }

        $this->write($pdf, 102, 565, 210, $leaveType);
    }

    private function writeLeaveDetails(Fpdi $pdf, LeaveRequest $leaveRequest, string $leaveType): void
    {
        $lower = strtolower($leaveType);
        if (str_contains($lower, 'vacation') || str_contains($lower, 'special privilege')) {
            $this->mark($pdf, 464, 278);
            $this->write($pdf, 585, 293, 140, $leaveRequest->reason, false, 8);
            return;
        }

        if (str_contains($lower, 'sick')) {
            $this->mark($pdf, 464, 354);
            $this->write($pdf, 610, 354, 110, $leaveRequest->reason, false, 8);
            return;
        }

        if (str_contains($lower, 'study')) {
            $this->mark($pdf, 464, 486);
            return;
        }

        $this->write($pdf, 535, 525, 190, $leaveRequest->reason, false, 8);
    }

    private function write(Fpdi $pdf, float $x, float $y, float $w, ?string $text, bool $uppercase = true, float $size = 8, string $align = 'L'): void
    {
        $text = trim((string) $text);
        if ($text === '') {
            return;
        }

        $pdf->SetFont('helvetica', 'B', $size);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 10, $uppercase ? mb_strtoupper($text) : $text, 0, 0, $align, false, '', 1);
    }

    private function writeMulti(Fpdi $pdf, float $x, float $y, float $w, float $h, ?string $text, bool $uppercase = true, float $size = 7): void
    {
        $text = trim((string) $text);
        if ($text === '') {
            return;
        }

        $pdf->SetFont('helvetica', '', $size);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($w, 9, $uppercase ? mb_strtoupper($text) : $text, 0, 'L', false, 1, '', '', true, 0, false, true, $h, 'T');
    }

    private function mark(Fpdi $pdf, float $x, float $y): void
    {
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY($x, $y);
        $pdf->Cell(8, 8, 'X', 0, 0, 'C');
    }

    private function drawCoordinateGuide(Fpdi $pdf): void
    {
        $pdf->SetDrawColor(255, 0, 0);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->SetFont('helvetica', '', 5);

        for ($x = 0; $x <= self::PAGE_WIDTH_PT; $x += 24) {
            $pdf->Line($x, 0, $x, self::PAGE_HEIGHT_PT);
            $pdf->Text($x + 1, 10, (string) $x);
        }

        for ($y = 0; $y <= self::PAGE_HEIGHT_PT; $y += 24) {
            $pdf->Line(0, $y, self::PAGE_WIDTH_PT, $y);
            $pdf->Text(2, $y + 6, (string) $y);
        }

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetTextColor(0, 0, 0);
    }

    private function date($date): string
    {
        return $date ? Carbon::parse($date)->format('F d, Y') : '';
    }

    private function inclusiveDates(LeaveRequest $leaveRequest): string
    {
        $start = Carbon::parse($leaveRequest->start_date);
        $end = Carbon::parse($leaveRequest->end_date);

        if ($start->isSameDay($end)) {
            return $start->format('F d, Y');
        }

        return $start->format('M d, Y').' - '.$end->format('M d, Y');
    }

    private function employeeName($employee): string
    {
        return trim(($employee?->firstname ?? '').' '.($employee?->lastname ?? ''));
    }
}
