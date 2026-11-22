<?php
use App\Models\{Employee, DtrRecord};
use App\Imports\AttendanceImport;
use PhpOffice\PhpSpreadsheet\{Spreadsheet, IOFactory};
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

test('updated spreadsheet library imports attendance dates and times without duplicate records', function () {
 $employee = Employee::create(['firstname'=>'Import','lastname'=>'Test','account_role'=>'employee']);
 $book = new Spreadsheet();
 $book->getActiveSheet()->fromArray([
  ['Employee ID','Name','Date','Time in','Time out','Status'],
  [$employee->id,'Import Test','2026-11-18','08:00','17:00','Present'],
 ]);
 $path = tempnam(sys_get_temp_dir(),'attendance-test-').'.xlsx';
 try {
  (new Xlsx($book))->save($path);
  (new AttendanceImport)->import($path);
  (new AttendanceImport)->import($path);
  expect(DtrRecord::where('employee_id',$employee->id)->count())->toBe(1);
  $this->assertDatabaseHas('dtr_records',['employee_id'=>$employee->id,'date'=>'2026-11-18','time_in'=>'08:00:00','time_out'=>'17:00:00']);
 } finally { $book->disconnectWorksheets(); if(is_file($path)) unlink($path); }
});

test('PDS workbook remains readable after the spreadsheet dependency upgrade', function () {
 $employee = Employee::create(['firstname'=>'Workbook','lastname'=>'Verification','account_role'=>'employee']);
 $response = app(\App\Services\PdsExcelExporter::class)->download($employee);
 $path = $response->getFile()->getPathname();
 try {
  $book = IOFactory::load($path);
  expect($book->getSheetCount())->toBeGreaterThan(0);
  $book->disconnectWorksheets();
 } finally { if(is_file($path)) unlink($path); }
});
