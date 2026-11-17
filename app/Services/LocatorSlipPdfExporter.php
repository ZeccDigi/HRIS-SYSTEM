<?php

namespace App\Services;

use App\Models\LocatorSlip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class LocatorSlipPdfExporter
{
    public function stream(LocatorSlip $locatorSlip): Response
    {
        $locatorSlip->loadMissing(['employee.user', 'employee.pdsGovId']);

        $filename = 'Locator_Slip_'.$locatorSlip->id.'.pdf';

        $pdf = Pdf::loadView('locator-slip.print', [
            'locatorSlip' => $locatorSlip,
            'pdfMode' => true,
            'previewMode' => false,
            'dmwLogoPath' => public_path('images/official DMW.png'),
            'bagongPilipinasLogoPath' => public_path('images/bagong-pilipinas-logo.png'),
            'signaturePath' => $locatorSlip->employee?->e_signature_absolute_path,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($filename, ['Content-Type' => 'application/pdf']);
    }
}
