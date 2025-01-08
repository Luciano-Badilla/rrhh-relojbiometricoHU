<?php

namespace App\Http\Controllers;

use App\Models\absenceReason;
use App\Models\attendance;
use App\Models\clockLogs;
use App\Models\NonAttendance;
use App\Models\category;
use App\Models\schedule_staff;
use App\Models\staff;
use App\Models\scale;
use App\Models\secretary;
use App\Models\coordinator;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        $absenceReasons = absenceReason::all();

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

        $workingDays = $this->getWorkingDays($staff->id, $month, $year);

        // Obtener todas las asistencias del mes para el staff
        $attendances = clockLogs::where('file_number', $file_number)
            ->whereMonth('timestamp', $month)
            ->whereYear('timestamp', $year)
            ->pluck('timestamp') // Solo obtener las fechas
            ->map(function ($timestamp) {
                return Carbon::parse($timestamp)->toDateString(); // Formato yyyy-mm-dd
            })
            ->toArray();

        // Comparar días laborales con asistencias y generar inasistencias si corresponde
        foreach ($workingDays as $workingDay) {
            $workingDay = Carbon::parse($workingDay)->format('Y-m-d'); // Formatear el día laboral

            // Verificar si ya existe una asistencia o una inasistencia para este día
            $attendanceExists = in_array($workingDay, $attendances);
            $nonAttendanceExist = NonAttendance::where([
                ['file_number', '=', $staff->file_number],
                ['date', '=', $workingDay]
            ])->exists();


            if (!$attendanceExists && !$nonAttendanceExist) {
                if ($workingDay != Carbon::now()->format('Y-m-d')) {
                    NonAttendance::create([
                        'file_number' => $staff->file_number,
                        'date' => $workingDay,
                    ]);
                }
            }
        }

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
            'workingDays' => $workingDays,
            'nonAttendance' => $nonAttendance->sortBy('date'),
            'absenceReasons' => $absenceReasons,
            'absenceReasonCount' => $absenceReasonCount
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

    public function update(Request $request, $id)
    {
        // Valida los datos del formulario
        $request->validate([
            'file_number' => 'required|string|max:255',
            'coordinator' => 'nullable|integer',
            'secretary' => 'nullable|integer',
            'name_surname' => 'required|string|max:255',
            'category' => 'nullable|integer',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        // Busca el registro en la base de datos
        $staff = Staff::findOrFail($id);

        // Actualiza los campos
        $staff->file_number = $request->input('file_number');
        $staff->coordinator_id = $request->input('coordinator');
        $staff->secretary_id = $request->input('secretary');
        $staff->name_surname = $request->input('name_surname');
        $staff->category_id = $request->input('category');
        $staff->email = $request->input('email');
        $staff->phone = $request->input('phone');
        $staff->address = $request->input('address');

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
            'Domingo' => 0,
        ];

        // Obtener los días de trabajo asignados al empleado
        $workingDays = schedule_staff::join('schedule', 'schedule_staff.schedule_id', '=', 'schedule.id')
            ->where('schedule_staff.staff_id', $staffId)
            ->select('schedule.day')
            ->pluck('day')
            ->map(function ($day) use ($daysMap) {
                return $daysMap[$day] ?? null;
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


        // Obtener los feriados del año desde la API
        $response = Http::get('https://api.argentinadatos.com/v1/feriados/' . $year);
        $holidays = $response->json();

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
