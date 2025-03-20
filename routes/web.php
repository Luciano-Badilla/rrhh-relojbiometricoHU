<?php

use App\Http\Controllers\attendanceController;
use App\Http\Controllers\clockLogsController;
use App\Http\Controllers\staffController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\reports;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');


Route::middleware(['auth', 'verified', CheckRole::class . ':1,2'])->group(function () {
    Route::get('/staff/administration_panel/{id}', [staffController::class, 'administration_panel'])->name('staff.administration_panel');
    Route::get('/staff/management/{id}', [staffController::class, 'management'])->name('staff.management');
    Route::get('/staff/attendance/{id}', [staffController::class, 'attendance'])->name('staff.attendance');
    Route::get('/clockLogs/update/{file_number?}', [clockLogsController::class, 'update_attendance'])->name('clockLogs.update');
});

Route::middleware(['auth', 'verified', CheckRole::class . ':2'])->group(function () {

    Route::get('/staff/list', [staffController::class, 'list'])->name('staff.list');
    Route::post('/staff/update/{id}', [StaffController::class, 'update'])->name('staff.update');

    Route::post('/schedule', [ScheduleController::class, 'store'])->name('schedule.store');

    Route::post('/attendance/add/{id?}', [attendanceController::class, 'add'])->name('attendance.add');
    Route::post('/absereason/add/{nonattendance_id?}', [attendanceController::class, 'add_absereason'])->name('absereason.add');
    Route::get('/clockLogs/backup', [clockLogsController::class, 'backup'])->name('clockLogs.backup');

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
