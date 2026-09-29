<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CtoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\EmployeeAccountController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LocatorSlipController;
use App\Http\Controllers\MyCtoController;
use App\Http\Controllers\MyLeaveController;
use App\Http\Controllers\PdsController;
use App\Http\Controllers\PdsSectionReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalnController;
use App\Http\Controllers\TravelOrderController;
use App\Http\Controllers\UtilityController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/locator-slips/{locatorSlip}/scan', [LocatorSlipController::class, 'scan'])->name('locator-slips.scan')->middleware('signed');

Route::middleware(['auth', 'account.active', 'password.changed'])->group(function () {
    Route::get('/personal-information', [EmployeeController::class, 'myRecord'])->name('personal-information.show');
    Route::get('/personal-information/edit', [EmployeeController::class, 'editMyRecord'])->name('personal-information.edit');
    Route::patch('/personal-information', [EmployeeController::class, 'updateMyRecord'])->name('personal-information.update');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'account.active', 'password.changed'])
    ->name('dashboard');

Route::get('/dashboard/leave-calendar', [DashboardController::class, 'leaveCalendar'])
    ->middleware(['auth', 'verified', 'account.active', 'password.changed'])
    ->name('dashboard.leave-calendar');

Route::middleware(['auth', 'account.active', 'approved', 'password.changed'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/employees/{employee}/profile-picture', [EmployeeController::class, 'uploadProfilePicture'])->name('employees.profile-picture');
    Route::post('/employees/{employee}/e-signature', [EmployeeController::class, 'uploadESignature'])->name('employees.e-signature');

    Route::get('/locator-slips', [LocatorSlipController::class, 'index'])->name('locator-slips.index');
    Route::get('/locator-slips/create', [LocatorSlipController::class, 'create'])->name('locator-slips.create');
    Route::post('/locator-slips', [LocatorSlipController::class, 'store'])->name('locator-slips.store');

    Route::patch('employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::delete('employees/{employee}/force-delete', [EmployeeController::class, 'forceDelete'])->name('employees.force-delete')->middleware('role:ADMIN');
    Route::get('employees/filter', [EmployeeController::class, 'filter'])->name('employees.filter')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::resource('employees', EmployeeController::class)->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index')->middleware('role:ADMIN,HRSTAFF');

    // Audit Log Routes
    Route::get('audit-logs/login', [AuditLogController::class, 'login'])->name('audit-logs.login')->middleware('role:ADMIN,HRSTAFF');
    Route::get('audit-logs/activity', [AuditLogController::class, 'activity'])->name('audit-logs.activity')->middleware('role:ADMIN,HRSTAFF');

    Route::get('employee-accounts', [EmployeeAccountController::class, 'index'])->name('employee-accounts.index')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::get('employee-accounts/{user}', [EmployeeAccountController::class, 'show'])->name('employee-accounts.show')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::patch('employee-accounts/{user}', [EmployeeAccountController::class, 'update'])->name('employee-accounts.update')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::patch('employee-accounts/{user}/approve', [EmployeeAccountController::class, 'approve'])->name('employee-accounts.approve')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::delete('employee-accounts/{user}/reject', [EmployeeAccountController::class, 'reject'])->name('employee-accounts.reject')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');

    Route::resource('leave-types', LeaveTypeController::class)->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::resource('holidays', HolidayController::class)->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');

    Route::get('/utilities', [UtilityController::class, 'index'])->name('utilities.index')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::post('/utilities/options', [UtilityController::class, 'storeOption'])->name('utilities.options.store')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::put('/utilities/options/{utilityOption}', [UtilityController::class, 'updateOption'])->name('utilities.options.update')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::delete('/utilities/options/{utilityOption}', [UtilityController::class, 'destroyOption'])->name('utilities.options.destroy')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');

    Route::resource('leaves', MyLeaveController::class);
    Route::get('leaves/{leaf}/print', [MyLeaveController::class, 'print'])->name('leaves.print');
    Route::get('leave-applications/filter', [LeaveApplicationController::class, 'filter'])->name('leave-applications.filter')->middleware('role:ADMIN,HRSTAFF,CHIEF,REGIONALDIRECTOR');
    Route::get('leave-applications/all/filter', [LeaveApplicationController::class, 'allFilter'])->name('leave-applications.all.filter')->middleware('role:ADMIN,HRSTAFF,CHIEF,REGIONALDIRECTOR');
    Route::get('leave-applications/all', [LeaveApplicationController::class, 'all'])->name('leave-applications.all')->middleware('role:ADMIN,HRSTAFF,CHIEF,REGIONALDIRECTOR');
    Route::get('leave-applications/{leaveApplication}/print', [LeaveApplicationController::class, 'print'])->name('leave-applications.print')->middleware('role:ADMIN,HRSTAFF,CHIEF,REGIONALDIRECTOR');
    Route::resource('leave-applications', LeaveApplicationController::class)->only(['index', 'update', 'show'])->middleware('role:ADMIN,HRSTAFF,CHIEF,REGIONALDIRECTOR');

    Route::get('/pds', [PdsController::class, 'edit'])->name('pds.index');
    Route::get('/pds/download', [PdsController::class, 'download'])->name('pds.download');
    Route::get('/pds/download-excel', [PdsController::class, 'downloadExcel'])->name('pds.download-excel');
    Route::get('/pds/print', [PdsController::class, 'print'])->name('pds.print');
    Route::get('/pds/print-clean', [PdsController::class, 'printClean'])->name('pds.print-clean');
    Route::get('/pds/edit', [PdsController::class, 'edit'])->name('pds.edit');
    Route::put('/pds', [PdsController::class, 'update'])->name('pds.update');
    Route::get('/employees/{employee}/pds', [PdsController::class, 'showForHr'])->name('employees.pds.show')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');

    Route::get('/view-announcements', [AnnouncementController::class, 'userIndex'])->name('announcements.view');
    Route::get('/announcements/filter', [AnnouncementController::class, 'filter'])->name('announcements.filter')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::get('/announcements/{announcement}/attachment', [AnnouncementController::class, 'attachment'])->name('announcements.attachment');
    Route::resource('announcements', AnnouncementController::class)->except(['show'])->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');
    Route::resource('announcements', AnnouncementController::class)->only(['show']);

    Route::get('my-dtr', [DtrController::class, 'myDtr'])->name('my-dtr.index');
    Route::get('my-cto', [MyCtoController::class, 'index'])->name('my-cto.index');
    Route::get('my-cto/create', [MyCtoController::class, 'create'])->name('my-cto.create');
    Route::post('my-cto', [MyCtoController::class, 'store'])->name('my-cto.store');
    Route::get('my-cto/{ctoRequest}/print', [MyCtoController::class, 'print'])->name('my-cto.print');
    Route::get('my-cto/{ctoRequest}', [MyCtoController::class, 'show'])->name('my-cto.show');
    Route::resource('dtr', DtrController::class)->only(['index', 'show']);
    Route::post('/dtr/import', [DtrController::class, 'syncFromFile'])->name('dtr.import');

    Route::get('/hr/locator-slips', [LocatorSlipController::class, 'manageIndex'])->name('hr.locator-slips.index')->middleware('role:ADMIN,HRSTAFF,CHIEF,REGIONALDIRECTOR');
    Route::get('/hr/locator-slips/all', [LocatorSlipController::class, 'allIndex'])->name('hr.locator-slips.all')->middleware('role:ADMIN,HRSTAFF,CHIEF,REGIONALDIRECTOR');
    Route::get('/hr/locator-slips/pending', [LocatorSlipController::class, 'pendingIndex'])->name('hr.locator-slips.pending')->middleware('role:ADMIN,HRSTAFF,CHIEF,REGIONALDIRECTOR');
    Route::get('/hr/locator-slips/{locatorSlip}', [LocatorSlipController::class, 'hrShow'])->name('hr.locator-slips.show')->middleware('role:ADMIN,HRSTAFF,CHIEF,REGIONALDIRECTOR');
    Route::get('/locator-slips/{locatorSlip}/print', [LocatorSlipController::class, 'print'])->name('locator-slips.print');
    Route::get('/locator-slips/{locatorSlip}', [LocatorSlipController::class, 'show'])->name('locator-slips.show');
    Route::patch('/locator-slips/{locatorSlip}/approve', [LocatorSlipController::class, 'approve'])->name('locator-slips.approve')->middleware('role:CHIEF');
    Route::patch('/locator-slips/{locatorSlip}/reject', [LocatorSlipController::class, 'reject'])->name('locator-slips.reject')->middleware('role:CHIEF');
    Route::get('/locator-slips/{locatorSlip}/edit', [LocatorSlipController::class, 'edit'])->name('locator-slips.edit');
    Route::put('/locator-slips/{locatorSlip}', [LocatorSlipController::class, 'update'])->name('locator-slips.update');

    Route::post('/employees/{employee}/pds-reviews', [PdsSectionReviewController::class, 'store'])->name('pds-reviews.store');
    Route::post('/employees/{employee}/pds-reviews/approve-all', [PdsSectionReviewController::class, 'approveAll'])->name('pds-reviews.approve-all')->middleware('role:ADMIN,HRSTAFF,REGIONALDIRECTOR');

    // SALN Routes
    Route::get('salns/{saln}/download', [SalnController::class, 'download'])->name('salns.download');
    Route::resource('salns', SalnController::class);

    // Travel Authority Routes
    Route::get('travel-orders/{travelOrder}/preview', [TravelOrderController::class, 'preview'])->name('travel-orders.preview');
    Route::get('travel-orders/{travelOrder}/print', [TravelOrderController::class, 'print'])->name('travel-orders.print');
    Route::resource('travel-orders', TravelOrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/travel-orders/{travelOrder}/tar', [TravelOrderController::class, 'submitTar'])->name('travel-orders.submit-tar');
    Route::get('/hr/travel-orders', [TravelOrderController::class, 'adminIndex'])->name('hr.travel-orders.index')->middleware('role:ADMIN,HRSTAFF,RECORDOFFICER,REGIONALDIRECTOR');
    Route::put('/travel-orders/{travelOrder}/status', [TravelOrderController::class, 'updateStatus'])->name('travel-orders.update-status')->middleware('role:RECORDOFFICER,REGIONALDIRECTOR');

    // Compensatory Time-Off Routes
    Route::get('/hr/cto', [CtoController::class, 'adminIndex'])->name('hr.cto.index')->middleware('role:ADMIN,HRSTAFF,CHIEF,REGIONALDIRECTOR');
    Route::put('/cto/{ctoRequest}/status', [CtoController::class, 'updateStatus'])->name('cto.update-status')->middleware('role:HRSTAFF,ADMIN,CHIEF,REGIONALDIRECTOR');
});

require __DIR__.'/auth.php';
