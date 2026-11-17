<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DtrPdfExporter
{
    public function stream(array $data, int $month, int $year): Response
    {
        $filename = sprintf('Daily_Time_Record_%04d_%02d.pdf', $year, $month);

        $pdf = Pdf::loadView('dtr.print', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true);

        return $pdf->stream($filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
