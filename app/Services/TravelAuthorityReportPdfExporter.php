<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TravelOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class TravelAuthorityReportPdfExporter
{
    public function stream(TravelOrder $travelOrder, ?Employee $divisionHead): Response
    {
        $travelOrder->loadMissing(['employee.user', 'companions']);

        $filename = 'Travel_Authority_Report_'.($travelOrder->ta_number ?: $travelOrder->id).'.pdf';

        $pdf = Pdf::loadView('travel-orders.acknowledgement-report-pdf', [
            'travelOrder' => $travelOrder,
            'divisionHead' => $divisionHead,
            'dmwLogoPath' => public_path('images/dmw.png'),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($filename, ['Content-Type' => 'application/pdf']);
    }
}
