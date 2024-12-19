<?php

namespace App\Http\Controllers;

use App\Models\attendance;
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

        $attendance = attendance::find($id);

        $attendance->entryTime = $entryTime;
        $attendance->departureTime = $departureTime;
        $attendance->observations = $observations;
        $attendance->update();

        return redirect()->back()->with('success','Asistencia editada correctamente');
    }
    
}
