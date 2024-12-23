<?php

use App\Http\Controllers\attendanceController;
use App\Http\Controllers\clockLogsController;
use App\Http\Controllers\staffController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/staff/management/{id}', [staffController::class, 'management'])->name('staff.management');
    Route::get('/staff/attendance/{id}', [staffController::class, 'attendance'])->name('staff.attendance');
    Route::get('/staff/list', [staffController::class, 'list'])->name('staff.list');

    Route::post('/attendance/add/{id?}', [attendanceController::class, 'add'])->name('attendance.add');
    
    Route::get('/clockLogs/update/{file_number?}', [clockLogsController::class, 'update_attendance'])->name('clockLogs.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
