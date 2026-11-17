<?php

namespace App\Services;

use App\Models\TravelOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class TravelAuthorityPdfExporter
{
    public function stream(TravelOrder $travelOrder): Response
    {
        $travelOrder->loadMissing(['employee', 'companions']);

        $filename = 'Travel_Authority_'.$travelOrder->id.'.pdf';

        $pdf = Pdf::loadView('travel-orders.pdf', [
            'travelOrder' => $travelOrder,
            // Official DMW seal with the built-in Tahanan ng OFW tagline.
            'dmwLogoPath' => public_path('images/official DMW.png'),
            'bagongPilipinasLogoPath' => public_path('images/bagong-pilipinas-logo.png'),
            'regionalOffice' => 'REGIONAL OFFICE XI, DAVAO CITY',
            'requestingOffice' => 'Regional Office XI',
            'notesRemarks' => $travelOrder->notes_remarks ?: '',
            'driverName' => $travelOrder->driver_name ?: '',
            'vehiclePlateNo' => $travelOrder->vehicle_plate_no ?: '',
            // DomPDF needs a local file path for the employee's saved e-signature.
            'requestingSignaturePath' => $travelOrder->employee?->e_signature_absolute_path,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($filename, ['Content-Type' => 'application/pdf']);
    }
}
