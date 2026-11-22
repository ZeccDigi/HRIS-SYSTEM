<?php

namespace App\Services;

use App\Models\CtoRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class CtoPdfExporter
{
    public function stream(CtoRequest $ctoRequest): Response
    {
        $ctoRequest->loadMissing(['employee.user', 'chief', 'hrstaff', 'regionalDirector']);

        $signaturePath = $ctoRequest->applicant_signature_path
            ? public_path('storage/'.$ctoRequest->applicant_signature_path)
            : $ctoRequest->employee?->e_signature_absolute_path;

        $pdf = Pdf::loadView($ctoRequest->type === 'earn' ? 'my-cto.print-coc-certificate' : 'my-cto.print-availment-new', [
            'ctoRequest' => $ctoRequest,
            'isPrintDocument' => true,
            'dmwLogoPath' => public_path('images/official DMW.png'),
            'bagongPilipinasLogoPath' => public_path('images/bagong-pilipinas-logo.png'),
            'applicantSignaturePath' => $signaturePath,
        ])->setPaper('a4', 'portrait');

        $filenamePrefix = $ctoRequest->type === 'earn' ? 'Earn_COC_' : 'CTO_Availment_';

        return $pdf->stream($filenamePrefix.$ctoRequest->reference.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
