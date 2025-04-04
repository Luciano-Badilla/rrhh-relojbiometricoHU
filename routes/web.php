<?php

use App\Http\Controllers\absenceController;
use App\Http\Controllers\attendanceController;
use App\Http\Controllers\clockLogsController;
use App\Http\Controllers\areaCoordinatorsController;
use App\Http\Controllers\categoryController;
use App\Http\Controllers\staffController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\reports;
use App\Http\Controllers\reportsController;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'verified', CheckRole::class . ':1,2'])->group(function () {
    Route::get('/', function () {
        return route('staff.list');
    })->name('dashboard');
    Route::get('/staff/administration_panel/{id}', [staffController::class, 'administration_panel'])->name('staff.administration_panel');
    Route::get('/staff/management/{id}', [staffController::class, 'management'])->name('staff.management');
    Route::get('/staff/attendance/{id}', [staffController::class, 'attendance'])->name('staff.attendance');
    Route::get('/clockLogs/update/{file_number?}', [clockLogsController::class, 'update_attendance'])->name('clockLogs.update');
});

Route::middleware(['auth', 'verified', CheckRole::class . ':2'])->group(function () {

    Route::get('/staff/list', [staffController::class, 'list'])->name('staff.list');
    Route::get('/staff/add', [staffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::post('/staff/update/{id}', [StaffController::class, 'update'])->name('staff.update');

    Route::post('/schedule', [ScheduleController::class, 'store'])->name('schedule.store');

    Route::post('/attendance/add/{id?}', [attendanceController::class, 'add'])->name('attendance.add');
    Route::post('/non_attendance/add_manual', [attendanceController::class, 'add_many_nonattendance'])->name('non_attendance.add_manual');
    Route::post('/attendance/add_manual', [attendanceController::class, 'add_manual'])->name('attendance.add_manual');
    Route::post('/absereason/add/{nonattendance_id?}', [attendanceController::class, 'add_absereason'])->name('absereason.add');
    Route::get('/clockLogs/backup', [clockLogsController::class, 'backup'])->name('clockLogs.backup');

    Route::get('/absencereason/list', [absenceController::class, 'list'])->name('absenceReason.list');
    Route::post('/absencereason/add', [absenceController::class, 'add'])->name('absenceReason.add');
    Route::post('/absencereason/edit', [absenceController::class, 'edit'])->name('absenceReason.edit');

    Route::get('/areaCoordinators/list', [areaCoordinatorsController::class, 'list'])->name('areaCoordinators.list');
    Route::post('/areaCoordinators/add', [areaCoordinatorsController::class, 'add'])->name('areaCoordinators.add');
    Route::post('/areaCoordinators/edit', [areaCoordinatorsController::class, 'edit'])->name('areaCoordinators.edit');

    Route::get('/category/list', [categoryController::class, 'list'])->name('category.list');
    Route::post('/category/add', [categoryController::class, 'add'])->name('category.add');
    Route::post('/category/edit', [categoryController::class, 'edit'])->name('category.edit');

    Route::get('/reports/nonAttendanceByArea', [reportsController::class, 'nonAttendanceByAreaIndex'])->name('reportView.nonAttendance');
    Route::get('/reports/nonAttendanceByArea/search', [reportsController::class, 'nonAttendanceByAreaSearch'])->name('reportSearch.nonAttendance');
    Route::post('/reports/nonAttendanceByArea/export', [reportsController::class, 'nonAttendanceByAreaExport'])->name('reportExport.nonAttendanceByArea');
    
    Route::get('/reports/tardiesByArea', [reportsController::class, 'tardiesByAreaIndex'])->name('reportView.tardies');
    Route::get('/reports/tardiesByArea/search', [reportsController::class, 'tardiesByAreaSearch'])->name('reportSearch.tardies');
    Route::post('/reports/tardiesByArea/export', [reportsController::class, 'tardiesByAreaExport'])->name('reportExport.tardiesByArea');

    Route::get('/individual_hours_report', [reports::class, 'individual_hours'])->name('report.individual_hours');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

Route::get('/unauthorized', function () {
    return response()->view('errors.unauthorized', [], 403);
})->name('unauthorized');


require __DIR__ . '/auth.php';
