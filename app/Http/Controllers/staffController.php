<?php

namespace App\Http\Controllers;

use App\Models\attendance;
use App\Models\category;
use App\Models\schedule_staff;
use App\Models\staff;
use App\Models\scale;
use App\Models\secretary;
use App\Models\coordinator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class staffController extends Controller
{
    public function management($id)
    {
        $staff = Staff::find($id); // Encuentra el registro del staff
        $categories = Category::all()->pluck('name', 'id'); 
        $scales = Scale::all()->pluck('name', 'id'); 
        $secretaries = Secretary::all()->pluck('name', 'id');

        // Obtén los coordinadores con los nombres del staff
        $coordinators = Coordinator::with('staff')
            ->get()
            ->pluck('staff.name_surname', 'id');

        // Pasa las variables a la vista
        return view('staff.management', [
            'staff' => $staff,
            'categories' => $categories,
            'scales' => $scales,
            'secretaries' => $secretaries,
            'coordinators' => $coordinators,
        ]);
    }


    public function attendance($id, Request $request)
    {
        $staff = staff::find($id);
        $file_number = $staff->file_number;
        $schedules = $staff->schedules;

        // Obtener mes y año actuales por si no están presentes en la solicitud
        $month = $request->input('month') ?? now()->month; // Mes actual si no se proporciona
        $year = $request->input('year') ?? now()->year; // Año actual si no se proporciona

        // Filtrar los registros de asistencia según el mes y el año
        $attendance = attendance::where('file_number', $file_number)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get()
            ->map(function ($item) use ($schedules) {
                // Formatear las fechas en formato dd/mm/yy
                $item->date = \Carbon\Carbon::parse($item->date)->format('d/m/y');
                $item->day = \Carbon\Carbon::createFromFormat('d/m/y', $item->date)->locale('es')->translatedFormat('l');
                $item->day = ucfirst($item->day);
                $item->hoursCompleted = $this->calculateWorkedHours($item->entryTime, $item->departureTime) ?? '00:00:00';

                // Inicializar las horas extra por defecto
                $item->extraHours = gmdate('H:i:s', 0);

                // Recorrer los horarios para calcular horas extra
                foreach ($schedules as $schedule) {
                    if ($schedule->day == $item->day) {
                        // Validar que no sea un registro vacío de asistencia
                        if (trim($item->entryTime) != trim($item->departureTime)) {
                            $startTime = Carbon::createFromFormat('H:i:s', $schedule->startTime);
                            $endTime = Carbon::createFromFormat('H:i:s', $schedule->endTime);
                            $hoursRequiredInSeconds = $startTime->diffInSeconds($endTime);

                            $hoursCompleted = Carbon::createFromFormat('H:i:s', $item->hoursCompleted);
                            $hoursRequired = Carbon::createFromFormat('H:i:s', gmdate('H:i:s', $hoursRequiredInSeconds));

                            // Comprobar si se completaron más horas de las requeridas
                            if ($hoursCompleted->greaterThan($hoursRequired)) {
                                $extraHoursInSeconds = $hoursCompleted->diffInSeconds($hoursRequired);
                                $item->extraHours = gmdate('H:i:s', $extraHoursInSeconds); // Formatear como hh:mm:ss
                            }
                        }
                        break; // Detener el bucle porque ya se encontró un horario coincidente
                    }
                }

                return $item;
            });


        $days = $attendance->pluck('date')->unique()->count();
        $hoursCompleted = $attendance->pluck('hoursCompleted');
        $extraHours = $attendance->pluck('extraHours');

        // Inicializar la suma total de horas en segundos
        $totalSeconds = 0;

        foreach ($hoursCompleted as $time) {
            // Separar horas, minutos y segundos
            $timeParts = explode(':', $time);
            $hours = (int) $timeParts[0]; // Horas
            $minutes = (int) $timeParts[1]; // Minutos
            $seconds = (int) $timeParts[2]; // Segundos

            // Calcular el total en segundos
            $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
        }

        // Calcular el promedio en segundos
        $count = 0;
        foreach ($hoursCompleted as $hour) {
            if ($hour != '00:00:00') {
                $count += 1;
            }
        }

        $extraHours = $attendance->pluck('extraHours');

        // Inicializar la suma total de horas extra en segundos
        $totalExtraSeconds = 0;

        // Calcular los segundos totales de horas extra
        foreach ($extraHours as $time) {
            // Separar horas, minutos y segundos
            $timeParts = explode(':', $time);
            $hours = (int) $timeParts[0]; // Horas
            $minutes = (int) $timeParts[1]; // Minutos
            $seconds = (int) $timeParts[2]; // Segundos

            // Calcular el total en segundos
            $totalExtraSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
        }

        $averageSeconds = $count > 0 ? $totalSeconds / $count : 0;

        // Convertir el promedio y el total a formato HH:mm
        $totalExtraHoursFormatted = sprintf('%02d:%02d:%02d', floor($totalExtraSeconds / 3600), floor(($totalExtraSeconds % 3600) / 60), $totalExtraSeconds % 60);
        $totalHoursFormatted = sprintf('%02d:%02d:%02d', floor($totalSeconds / 3600), floor(($totalSeconds % 3600) / 60), $totalSeconds % 60);
        $hoursAverageFormatted = sprintf('%02d:%02d:%02d', floor($averageSeconds / 3600), floor(($averageSeconds % 3600) / 60), $averageSeconds % 60);


        return view('staff.attendance', [
            'staff' => $staff,
            'attendance' => $attendance->sortBy('date'),
            'month' => $month,
            'year' => $year,
            'days' => $days,
            'hoursAverage' => $hoursAverageFormatted,
            'totalHours' => $totalHoursFormatted,
            'schedules' => $schedules,
            'totalExtraHours' => $totalExtraHoursFormatted
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
