<?php

namespace App\Services;

use App\Models\Employee;
use App\Services\Pds\Cs212PdfGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;
use Symfony\Component\Process\Process;

class PdsPdfExporter
{
    public function __construct(
        private readonly Cs212PdfGenerator $generator
    ) {}

    public function download(Employee $employee, string $disposition = 'attachment'): Response
    {
        $employee->load([
            'user',
            'pdsPersonal', 'pdsFamily', 'pdsChildren', 'pdsEducation',
            'pdsEligibilities', 'pdsWorkExperiences', 'pdsVoluntaryWorks',
            'pdsTrainings', 'pdsOthers', 'pdsQuestionnaire', 'pdsReferences', 'pdsGovId',
        ]);

        $browser = $this->browserPath();
        $workDir = storage_path('app/pds-pdf/'.uniqid('pds_', true));
        File::ensureDirectoryExists($workDir);

        $htmlPath = $workDir.DIRECTORY_SEPARATOR.'pds.html';
        $pdfPath = $workDir.DIRECTORY_SEPARATOR.'pds.pdf';
        $profilePath = $workDir.DIRECTORY_SEPARATOR.'browser-profile';

        File::put($htmlPath, view('pds.print-2025', compact('employee'))->render());

        $process = new Process([
            $browser,
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--print-to-pdf='.$pdfPath,
            '--print-to-pdf-no-header',
            '--user-data-dir='.$profilePath,
            'file:///'.str_replace('\\', '/', $htmlPath),
        ]);
        $process->setTimeout(90);
        $process->run();

        if (! $process->isSuccessful() || ! file_exists($pdfPath)) {
            File::deleteDirectory($workDir);

            throw new RuntimeException('Could not generate PDS PDF from the preview layout. '.$process->getErrorOutput());
        }

        $personal = $employee->pdsPersonal;
        $surname = $personal?->surname ?? $employee->lastname;
        $filename = 'PDS_CS212_'.preg_replace('/\s+/', '_', $surname).'_'.now()->format('Y-m-d').'.pdf';

        $contents = File::get($pdfPath);
        File::deleteDirectory($workDir);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    private function browserPath(): string
    {
        $candidates = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new RuntimeException('Chrome or Microsoft Edge is required to download the PDS PDF from the preview layout.');
    }
}
