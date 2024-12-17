<?php

namespace App\Http\Controllers;

use App\Models\attendance;
use App\Models\staff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        // Filtrar los registros de asistencia según el mes y el año
        $attendance = attendance::where('file_number', $file_number)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get()
            ->map(function ($item) {
                // Formatear las fechas en formato dd/mm/yy
                $item->date = \Carbon\Carbon::parse($item->date)->format('d/m/y');
                $item->day = \Carbon\Carbon::createFromFormat('d/m/y', $item->date)->locale('es')->translatedFormat('l');
                $item->day = ucfirst($item->day);
                $item->hoursCompleted = $this->calculateWorkedHours($item->entryTime, $item->departureTime) ?? '00:00:00';
                return $item;
            });

        $days = $attendance->pluck('date')->unique()->count();
        $hoursCompleted = $attendance->pluck('hoursCompleted');

        // Inicializar la suma total de horas en segundos
        $totalSeconds = 0;

        foreach ($hoursCompleted as $time) {
            // Separar horas, minutos y segundos
            $timeParts = explode(':', $time);
            $hours = (int)$timeParts[0]; // Horas
            $minutes = (int)$timeParts[1]; // Minutos
            $seconds = (int)$timeParts[2]; // Segundos

            // Calcular el total en segundos
            $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
        }

        // Convertir el total de segundos a horas decimales
        $totalHoursDecimal = $totalSeconds / 3600;

        // Calcular el promedio en segundos
        $count = $hoursCompleted->count() - 1;
        $averageSeconds = $count > 0 ? $totalSeconds / $count : 0;

        // Convertir el promedio y el total a formato HH:mm
        $totalHoursFormatted = sprintf('%02d:%02d', floor($totalSeconds / 3600), floor(($totalSeconds % 3600) / 60));
        $hoursAverageFormatted = sprintf('%02d:%02d', floor($averageSeconds / 3600), floor(($averageSeconds % 3600) / 60));

        return view('staff.attendance', [
            'staff' => $staff,
            'attendance' => $attendance,
            'month' => $month,
            'year' => $year,
            'days' => $days,
            'hoursAverage' => $hoursAverageFormatted,
            'totalHours' => $totalHoursFormatted
        ]);
    }


    public function list()
    {

        $staff = staff::all();

        return view('staff.list', ['staff' => $staff]);
    }

    
    private function calculateWorkedHours($entryTime, $exitTime)
{
    $entryTimestamp = strtotime($entryTime);
    $exitTimestamp = strtotime($exitTime);

    // Calcular la diferencia en segundos
    $workedSeconds = $exitTimestamp - $entryTimestamp;

    // Convertir los segundos a horas, minutos y segundos
    $hours = floor($workedSeconds / 3600); // Horas completas
    $minutes = floor(($workedSeconds % 3600) / 60); // Minutos restantes
    $seconds = $workedSeconds % 60; // Segundos restantes

    // Retornar en formato H:i:s
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

}
