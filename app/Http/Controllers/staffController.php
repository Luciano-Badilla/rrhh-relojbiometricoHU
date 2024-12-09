<?php

namespace App\Http\Controllers;

use App\Models\attendance;
use App\Models\staff;
use Illuminate\Http\Request;

class staffController extends Controller
{
    public function management($id)
    {
        $staff = staff::find($id);
        return view('staff.management', ['staff' => $staff]);
    }
    public function attendance($id, Request $request)
    {
        $staff = staff::find($id);
        $file_number = $staff->file_number;


        // Obtener mes y año actuales por si no están presentes en la solicitud
        $month = $request->input('month') ?? now()->month; // Mes actual si no se proporciona
        $year = $request->input('year') ?? now()->year; // Año actual si no se proporciona

        if ($request->input('month') && $request->input('year')) {
            // Si existen ambos parámetros, filtrar los registros de asistencia según el mes y año
            $attendance = attendance::where('file_number', $file_number)
                ->whereMonth('date', $request->input('month'))
                ->whereYear('date', $request->input('year'))
                ->get();
        } else {
            // Filtrar los registros de asistencia según el mes y el año
            $attendance = attendance::where('file_number', $file_number)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get();
        }

        return view('staff.attendance', ['staff' => $staff, 'attendance' => $attendance, 'month' => $month, 'year' => $year]);
    }
    public function list()
    {

        $staff = staff::all();

        return view('staff.list', ['staff' => $staff]);
    }
}
