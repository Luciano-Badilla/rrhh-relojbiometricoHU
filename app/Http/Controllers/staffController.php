<?php

namespace App\Http\Controllers;

use App\Models\absenceReason;
use App\Models\attendance;
use App\Models\clockLogs;
use App\Models\NonAttendance;
use App\Models\category;
use App\Models\schedule_staff;
use App\Models\shift;
use App\Models\area;
use App\Models\staff;
use App\Models\schedule;
use App\Models\scale;
use App\Models\secretary;
use App\Models\coordinator;
use App\Models\vacations;
use App\Models\annual_vacation_days;
use App\Models\day;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class staffController extends Controller
{
    public function create()
    {
        $areas = Area::all(); // Obtiene todas las áreas
        $coordinators = Coordinator::with('staff')->get()->pluck('staff.name_surname', 'id');
        $secretaries = Secretary::all()->pluck('name', 'id');
        $categories = Category::all()->pluck('name', 'id');

        return view('staff.create', compact('areas', 'coordinators', 'secretaries', 'categories'));
    }

    public function store(Request $request)
    {
        // Validación de datos
        $validatedData = $request->validate([
            'name_surname' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'file_number' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer',
            'coordinator_id' => 'nullable|integer',
            'secretary_id' => 'nullable|integer',
            'date_of_entry' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'worker_status' => 'nullable|string',
            'area_id' => 'nullable|integer',
        ]);

        // Convertir la fecha de dd/mm/yyyy a yyyy-mm-dd si existe
        if ($request->filled('date_of_entry')) {
            $dateParts = explode('/', $request->input('date_of_entry'));
            if (count($dateParts) === 3) {
                $validatedData['date_of_entry'] = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}"; // yyyy-mm-dd
            }
        }

        // Crear nuevo staff
        $staff = Staff::create($validatedData);
        $areas = $request->input('areas', []);
        $staff->areas()->sync($areas);

        return redirect()->route('staff.management', ['id' => $staff->id])->with('success', 'Staff creado exitosamente.');
    }



    public function administration_panel($id)
    {
        $staff = Staff::find($id); // Encuentra el registro del staf

        // Pasa las variables a la vista
        return view('staff.administration_panel', [
            'staff' => $staff
        ]);
    }

    public function management($id)
    {
        $staff = Staff::find($id); // Encuentra el registro del staff
        $categories = Category::all()->pluck('name', 'id');
        $scales = Scale::all()->pluck('name', 'id');
        $secretaries = Secretary::all()->pluck('name', 'id');
        $schedules = $staff->schedules;
        $vacations = Vacations::where('staff_id', $staff->file_number)->orderBy('year')->get();
        $annual_vacation_days = annual_vacation_days::where('staff_id', $staff->file_number)
            ->first();

        $coordinators = Coordinator::with('staff')
            ->get()
            ->pluck('staff.name_surname', 'id');
        // Todas las áreas
        $areas = Area::all()->pluck('name', 'id')->toArray(); // Array asociativo

        // Áreas asignadas al staff
        $assigned_areas = $staff->areas->pluck('id')->toArray(); // IDs seleccionados

        // Pasa las variables a la vista
        return view('staff.management', [
            'staff' => $staff,
            'categories' => $categories,
            'scales' => $scales,
            'secretaries' => $secretaries,
            'coordinators' => $coordinators,
            'vacations' => $vacations,
            'schedules' => $schedules,
            'annual_vacation_days' => $annual_vacation_days,
            'areas' => $areas, // Todas las áreas
            'assigned_areas' => $assigned_areas, // Áreas asignadas
        ]);
    }





    public function attendance($id, Request $request)
    {

        $staff = staff::find($id);
        $file_number = $staff->file_number;

        $schedules = $staff->schedules;
        // Definir el orden de los días
        $order = [
            'Lunes' => 1,
            'Martes' => 2,
            'Miércoles' => 3,
            'Jueves' => 4,
            'Viernes' => 5,
            'Sábado' => 6,
            'Domingo' => 7,
        ];

        // Reordenar la colección según el orden definido
        $schedules = $schedules->sortBy(function ($schedule) use ($order) {
            return $order[$schedule->day_id] ?? 8; // Si no coincide, poner al final
        });

        // Para preservar los índices originales, utiliza sortBy y valores por referencia
        $schedules = $schedules->values();

        $absenceReasons = absenceReason::all();

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
    
                $item->date_formated = \Carbon\Carbon::parse($item->date)->format('d/m/y');
                $item->hoursCompleted = $this->calculateWorkedHours($item->entryTime, $item->departureTime) ?? gmdate('H:i:s', 0);

                return $item;
            });


        $days = $attendance->filter(function ($item) {
            return $item->departureTime !== $item->entryTime;
        })->pluck('date')->unique()->count();

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

        $attendanceDates = Attendance::where('file_number', $file_number)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->pluck('date')
            ->toArray();

        // Filtrar y eliminar las inasistencias que coincidan con fechas de asistencia
        $nonAttendance = NonAttendance::where('file_number', $file_number)->with('absenceReason')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)->get()->map(function ($item) use ($schedules, $attendanceDates) {
                // Verificar si la fecha de inasistencia coincide con una fecha de asistencia
                if (in_array($item->date, $attendanceDates)) {
                    NonAttendance::where('id', $item->id)->delete();
                    return null; // Excluir del resultado
                }

                // Formatear las fechas en formato dd/mm/yy
                $item->date = \Carbon\Carbon::parse($item->date)->format('d/m/y');
                $item->day = \Carbon\Carbon::createFromFormat('d/m/y', $item->date)->locale('es')->translatedFormat('l');
                $item->day = ucfirst($item->day);
                $item->absenceReason = $item->absenceReason->name ?? null;

                return $item;
            })->filter(); // Filtrar nulos después de eliminar

        $absenceReasonCount = $nonAttendance
            ->filter(function ($item) {
                return !empty($item->absenceReason); // Excluir razones vacías
            })
            ->groupBy('absenceReason')
            ->map(function ($items, $reason) {
                return (object) [
                    'name' => $reason,       // Nombre del tipo de ausencia
                    'count' => count($items) // Cantidad de inasistencias de ese tipo
                ];
            });

        $dataToExport = [
            'staff' => $staff,
            'hoursAverage' => $hoursAverageFormatted,
            'totalHours' => $totalHoursFormatted,
            'totalExtraHours' => $totalExtraHoursFormatted,
            'month' => $month,
            'year' => $year,
            'days' => $days
        ];

        return view('staff.attendance', [
            'staff' => $staff,
            'attendance' => $attendance->sortBy('date'),
            'month' => $month,
            'year' => $year,
            'days' => $days,
            'hoursAverage' => $hoursAverageFormatted,
            'totalHours' => $totalHoursFormatted,
            'schedules' => $schedules,
            'totalExtraHours' => $totalExtraHoursFormatted,
            'nonAttendance' => $nonAttendance->sortBy('date'),
            'absenceReasons' => $absenceReasons->sortBy('name'),
            'absenceReasonCount' => $absenceReasonCount,
            'dataToExport' => $dataToExport
        ]);
    }


    public function list()
    {

        $staff = staff::all();

        return view('staff.list', [
            'staff' => $staff->sortBy('name_surname'),
        ]);
    }


    public function calculateWorkedHours($entryTime, $exitTime)
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

    public function update(Request $request, $id)
    {
        // Valida los datos del formulario
        $request->validate([
            'file_number' => 'required|string|max:255',
            'coordinator' => 'nullable|integer',
            'secretary' => 'nullable|integer',
            'name_surname' => 'required|string|max:255',
            'category' => 'nullable|integer',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'date_of_entry' => 'nullable|string',
            'worker_status' => 'nullable',
        ]);

        $areas = $request->input('areas', []);

        $staff = Staff::findOrFail($id);
        $staff->areas()->sync($areas);

        $staff->file_number = $request->input('file_number');
        $staff->coordinator_id = $request->input('coordinator');
        $staff->secretary_id = $request->input('secretary');
        $staff->name_surname = $request->input('name_surname');
        $staff->category_id = $request->input('category');
        $staff->email = $request->input('email');
        $staff->phone = $request->input('phone');
        $staff->address = $request->input('address');

        // Convertir la fecha de dd/mm/yyyy a yyyy-mm-dd antes de guardarla
        $dateOfEntry = $request->input('date_of_entry');
        if ($dateOfEntry) {
            $dateParts = explode('/', $dateOfEntry); // Divide la fecha en partes
            if (count($dateParts) === 3) {
                $formattedDate = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}"; // Reorganiza como yyyy-mm-dd
                $staff->date_of_entry = $formattedDate;
            }
        }

        $staff->worker_status = $request->input('worker_status');

        // Guarda los cambios en la base de datos
        $staff->save();

        // Redirige con un mensaje de éxito
        return redirect()->back()->with('success', 'Datos actualizados correctamente.');
    }


    public function getWorkingDays($staffId, $month, $year)
    {
        Carbon::setLocale('es');
        $today = ($year == Carbon::now()->year && $month == Carbon::now()->month)
            ? Carbon::create($year, $month, Carbon::now()->day)
            : Carbon::create($year, $month)->endOfMonth();

        $startOfMonth = $today->copy()->startOfMonth();


        // Mapeo de días en español a formato numérico
        $daysMap = [
            'Lunes' => 1,
            'Martes' => 2,
            'Miércoles' => 3,
            'Jueves' => 4,
            'Viernes' => 5,
            'Sábado' => 6,
            'Domingo' => 7,
        ];

        // Obtener los días de trabajo asignados al empleado
        $workingDays = schedule_staff::join('schedule', 'schedule_staff.schedule_id', '=', 'schedule.id')
            ->join('days', 'schedule.day_id', '=', 'days.id')  // Unimos con la tabla 'days' usando 'day_id'
            ->where('schedule_staff.staff_id', $staffId)
            ->select('days.name')  // Seleccionamos el nombre del día
            ->pluck('name')  // Obtenemos los días en español
            ->map(function ($day) use ($daysMap) {

                return $daysMap[$day] ?? null;  // Mapeamos al formato numérico si es necesario
            })
            ->filter()
            ->toArray();

        // Crear un rango de fechas y filtrar por los días laborales
        $dates = [];
        for ($date = $today; $date >= $startOfMonth; $date->subDay()) {
            if (in_array($date->dayOfWeek, $workingDays)) {
                $dates[] = $date->toDateString();
            }
        }

        dd($dates);


        // Obtener los feriados del año desde la API
        $response = Http::get('https://api.argentinadatos.com/v1/feriados/' . $year);
        $holidays = $response->json();
        //dd($holidays);
        // Filtrar los días laborales excluyendo los feriados
        $dates = array_filter($dates, function ($date) use ($holidays) {
            // Compara las fechas laborales con los feriados
            foreach ($holidays as $holiday) {
                if ($holiday['fecha'] == $date) {
                    return false; // Si es un feriado, lo excluye
                }
            }
            return true; // Si no es un feriado, lo incluye
        });
        return $dates;
    }
}
