<?php

namespace App\Http\Controllers;

use App\Models\attendance;
use App\Models\clockLogs;
use App\Models\NonAttendance;
use App\Models\schedule_staff;
use App\Models\staff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class attendanceController extends Controller
{
    public function edit(request $request)
    {
        $id = $request->input('attendance_id');
        $entryTime = $request->input('entryTime');
        $departureTime = $request->input('departureTime');
        $observations = $request->input('observations');
        $file_number = $request->input('file_number');

        $attendance = attendance::find($id);

        $attendance->entryTime = $entryTime;
        $attendance->departureTime = $departureTime;
        $attendance->observations = $observations;
        $attendance->update();

        $datetime1 = $attendance->date . ' ' . $entryTime;
        $datetime2 = $attendance->date . ' ' . $departureTime;

        $timestamp1 = date('Y-m-d H:i:s', strtotime($datetime1));
        $timestamp2 = date('Y-m-d H:i:s', strtotime($datetime2));

        $clockLog = clockLogs::where('file_number', $file_number)->where('timestamp', $timestamp1)->exists();
        $clockLog2 = clockLogs::where('file_number', $file_number)->where('timestamp', $timestamp2)->exists();

        if (!$clockLog) {
            clockLogs::create([
                'file_number' => $file_number,
                'timestamp' => $timestamp1,
                'device_id' => clockLogs::where('file_number', $file_number)->first()->device_id
            ]);
        }

        if (!$clockLog2) {
            clockLogs::create([
                'file_number' => $file_number,
                'timestamp' => $timestamp2,
                'device_id' => clockLogs::where('file_number', $file_number)->first()->device_id
            ]);
        }

        return redirect()->back()->with('success', 'Asistencia editada correctamente');
    }

    public function add(request $request)
    {
        $id = $request->input('attendance_id');
        $attendance_time = $request->input('attendance_time');
        $observations = $request->input('observations');
        $file_number = $request->input('file_number');

        $attendance = attendance::find($id);
        $attendance->observations = $observations;
        $attendance->update();

        $datetime1 = $attendance->date . ' ' . $attendance_time;

        $timestamp1 = date('Y-m-d H:i:s', strtotime($datetime1));

        $clockLog = clockLogs::where('file_number', $file_number)->where('timestamp', $timestamp1)->exists();

        if (!$clockLog) {
            clockLogs::create([
                'file_number' => $file_number,
                'timestamp' => $timestamp1,
                'device_id' => clockLogs::where('file_number', $file_number)->first()->device_id
            ]);
        }

        return redirect()->back()->with('success', 'Asistencia editada correctamente');
    }

    public function add_absereason(request $request, $nonattendance_id)
    {
        $id = $nonattendance_id;
        $absenceReason = $request->input('absenceReason');

        $nonattendance = NonAttendance::find($id);
        $nonattendance->absenceReason_id = $absenceReason;
        $nonattendance->update();

        return redirect()->back()->with('success', 'Inasistencia justificada correctamente');
    }
}
