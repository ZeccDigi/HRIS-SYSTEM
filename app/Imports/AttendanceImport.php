<?php

namespace App\Imports;

use App\Models\DtrRecord;


use Carbon\Carbon;

class AttendanceImport
{
    /**
     * Skip the header row (Start at row 2)
     */
    public function import(string $path): void
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $workbook = $reader->load($path);
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($workbook) {
                foreach ($workbook->getActiveSheet()->toArray(null, false, false, false) as $index => $row) {
                    if ($index + 1 >= $this->startRow()) $this->model($row);
                }
            });
        } finally { $workbook->disconnectWorksheets(); }
    }

    public function startRow(): int
    {
        return 2;
    }

    /**
    * @param array $row
    */
    public function model(array $row)
    {
        // 1. Check if Employee ID (Column A) and Time In (Column D) are present
        // row[0] = Column A, row[3] = Column D
        if (!isset($row[0]) || empty($row[0]) || !isset($row[3]) || empty($row[3])) {
            return null; // Skip this row if basic data is missing
        }

        $date    = $this->transformDate($row[2]);
        $timeIn  = $this->transformTime($row[3]);
        $timeOut = $this->transformTime($row[4] ?? null);

        // 2. Extra safety: If parsing failed and resulted in null for a required field
        if (!$date || !$timeIn) {
            return null;
        }

        return DtrRecord::updateOrCreate(
            [
                'employee_id' => $row[0],
                'date'        => $date,
                'time_in'     => $timeIn,
            ],
            [
                'time_out'    => $timeOut,
                'status'      => $row[5] ?? 'Present',
            ]
        );
    }

    private function transformDate($value)
    {
        if (!$value) return null;
        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function transformTime($value)
    {
        if (!$value) return null;
        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('H:i:s');
            }
            // Handle common string formats like "08:00 AM" or "17:00"
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
