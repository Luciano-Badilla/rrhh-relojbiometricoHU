<?php

namespace App\Http\Controllers;

use App\Models\absenceReason;
use App\Models\attendance;
use App\Models\clockLogs;
use App\Models\NonAttendance;
use App\Models\category;
use App\Models\schedule_staff;
use App\Models\shift;
use App\Models\collective_agreement;
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
use Illuminate\Support\Facades\Auth;
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
            'file_number' => 'string|max:255',
            'category_id' => 'nullable|integer',
            'coordinator_id' => 'nullable|integer',
            'secretary_id' => 'nullable|integer',
            'date_of_entry' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'worker_status' => 'nullable|string',
            'area_id' => 'nullable|integer',
        ]);

        //dd($request->all());

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

        $currentYear = now()->year;
        $previousYear = $currentYear - 1;

        $annual_vacation_days = annual_vacation_days::where('staff_id', $staff->file_number)->value('days');

        $annual_vacation_days = $annual_vacation_days ?? 0;

        // Verifica si el año anterior ya está registrado
        $existing = vacations::where('staff_id', $staff->file_number)
            ->where('year', $previousYear)
            ->exists();

        //dd($existing);
        if (!$existing) {
            $nuevaVac = vacations::create([
                'staff_id' => $staff->file_number,
                'year' => $previousYear,
                'days' => $annual_vacation_days, // o el valor que manejes por defecto
            ]);
        }
        //dd($nuevaVac);

        $categories = Category::all()->pluck('name', 'id');
        $collective_agreement = collective_agreement::all();
        $secretaries = Secretary::all()->pluck('name', 'id');
        $schedules = $staff->schedules;
        $vacations = vacations::where('staff_id', $staff->file_number)->orderBy('year')->get();
        $annual_vacation_days = annual_vacation_days::where('staff_id', $staff->file_number)
            ->first();

        $coordinators = Coordinator::with('staff')
            ->get()
            ->pluck('staff.name_surname', 'id');
        // Todas las áreas
        $areas = Area::all()->pluck('name', 'id')->toArray(); // Array asociativo

        // Áreas asignadas al staff
        $assigned_areas = $staff->areas->pluck('id')->toArray(); // IDs seleccionados

        //dd($staff->id);
        // Pasa las variables a la vista
        return view('staff.management', [
            'staff' => $staff,
            'categories' => $categories,
            'collective_agreement' => $collective_agreement,
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
        $vacations = Vacations::where('staff_id', $staff->file_number)->get();
        $totalVacationDays = 0;
        foreach ($vacations as $vacation) {
            $totalVacationDays += $vacation->days;
        }
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

        if ($staff->collective_agreement) {
            $absenceReasons = absenceReason::where('decree', $staff->collective_agreement->name)->where('logical_erase', false)->get();
        } else {
            $absenceReasons = collect(); // Devuelve una colección vacía si no hay convenio colectivo
        }


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
            'dataToExport' => $dataToExport,
            'totalVacationDays' => $totalVacationDays,
            'vacations' => $vacations
        ]);
    }


    public function list()
    {
        $staff = Staff::all()->map(function ($item) {
            $item->areas_name = $item->areas->pluck('name')->implode(', ') ?: 'Sin área asignada';
            return $item;
        });

        $areas = area::all();

        // Ordenar: primero los activos, luego los dados de baja, y dentro de cada grupo por nombre
        $staff = $staff->sortBy(function ($item) {
            return [$item->inactive_since ? 1 : 0, $item->name_surname];
        });

        return view('staff.list', [
            'staff' => $staff,
            'areas' => $areas
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
        //dd($request->all());
        $request->validate([
            'file_number' => 'required|string|max:255',
            'coordinator' => 'nullable|integer',
            'secretary' => 'nullable|integer',
            'collective_agreement_id' => 'nullable|integer',
            'name_surname' => 'required|string|max:255',
            'category' => 'nullable|integer',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'date_of_entry' => 'nullable|string',
            'worker_status' => 'nullable|string',
            'inactive' => 'nullable|boolean',
            'marking' => 'nullable|boolean',
        ]);

        $areas = $request->input('areas', []);

        $staff = Staff::findOrFail($id);
        $staff->areas()->sync($areas);
        $annualVacationDays = $request->input('annual_vacation_days');

        Annual_Vacation_Days::updateOrCreate(
            ['staff_id' => $staff->file_number],
            ['days' => $annualVacationDays]
        );

        $staff->file_number = $request->input('file_number');
        $staff->coordinator_id = $request->input('coordinator');
        $staff->secretary_id = $request->input('secretary');
        $staff->collective_agreement_id = $request->input('collective_agreement_id');
        $staff->name_surname = $request->input('name_surname');
        $staff->category_id = $request->input('category');
        $staff->email = $request->input('email');
        $staff->phone = $request->input('phone');
        $staff->address = $request->input('address');

        // Convertir la fecha de dd/mm/yyyy a yyyy-mm-dd antes de guardarla
        if ($request->filled('date_of_entry')) {
            $dateParts = explode('/', $request->input('date_of_entry'));
            if (count($dateParts) === 3) {
                $staff->date_of_entry = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}";
            }
        }

        $staff->worker_status = $request->input('worker_status');

        // Si "Baja" está marcado, guardamos la fecha actual en "inactive_since"
        if ($request->input('inactive')) {
            $staff->inactive_since = now()->format('Y-m-d'); // Guarda la fecha actual en formato yyyy-mm-dd
        } else {
            $staff->inactive_since = null; // Si no está marcado, deja el campo vacío
        }

        // Si "Marca" está marcado, guarda "1", si no, guarda "0"
        $staff->marking = $request->input('marking', 0);

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

    public function vacations_add(Request $request)
    {
        $staff = Staff::find($request->input('staff_id'));
        $date_from = Carbon::parse($request->input('vacations_date_from'));
        $date_to = Carbon::parse($request->input('vacations_date_to'));
        $totalDays = $date_to->diffInDays($date_from) + 1;
        $observations = $request->input('observations') . ' - ' . Auth::user()->name . ' ' . Carbon::now()->format('d/m/Y H:i');

        $vacations = Vacations::where('staff_id', $staff->file_number)
            ->orderBy('year', 'asc')
            ->get();

        $currentDate = $date_from->copy();

        // Para feedback
        $conflictedDates = [];
        $attendanceDates = [];
        $usedDays = 0;

        while ($totalDays > 0 && $currentDate->lte($date_to)) {
            // Verificar si hay asistencia registrada para ese día
            $hasAttendance = Attendance::where('file_number', $staff->file_number)
                ->whereDate('date', $currentDate)
                ->exists();

            if ($hasAttendance) {
                $attendanceDates[] = $currentDate->format('d/m/Y');
                $currentDate->addDay();
                continue;
            }

            $existing = NonAttendance::where('file_number', $staff->file_number)
                ->where('date', $currentDate->format('Y-m-d'))
                ->first();

            if ($existing && !is_null($existing->absenceReason_id)) {
                // Día ocupado con otro motivo
                $conflictedDates[] = $currentDate->format('d/m/Y');
                $currentDate->addDay();
                continue;
            }

            $vacation = $vacations->first(function ($v) {
                return $v->days > 0;
            });

            if (!$vacation) {
                // Sin más días disponibles
                break;
            }

            $absenceReason = AbsenceReason::firstOrCreate(
                ['name' => 'Vacaciones ' . $vacation->year],
                ['logical_erase' => 1]
            );

            if ($existing && is_null($existing->absenceReason_id)) {
                $existing->update([
                    'absenceReason_id' => $absenceReason->id,
                    'observations' => $observations,
                ]);
            } elseif (!$existing) {
                NonAttendance::create([
                    'file_number' => $staff->file_number,
                    'date' => $currentDate->format('Y-m-d'),
                    'absenceReason_id' => $absenceReason->id,
                    'observations' => $observations,
                ]);
            }

            // Solo descontar si se usó el día
            $vacation->days -= 1;
            $vacation->save();

            $usedDays++;
            $totalDays--;
            $currentDate->addDay();
        }

        // Mensajes personalizados
        if ($usedDays === 0 && count($conflictedDates) > 0 && count($attendanceDates) === 0) {
            return redirect()->back()->with('error', 'No se registraron vacaciones. Todos los días ya están justificados.');
        }

        if ($usedDays === 0 && count($attendanceDates) > 0 && count($conflictedDates) === 0) {
            return redirect()->back()->with('error', 'No se registraron vacaciones. Los siguientes días ya tienen asistencias registradas: ' . implode(', ', $attendanceDates));
        }

        if ($usedDays === 0) {
            return redirect()->back()->with('error', 'No se registraron vacaciones. Todos los días ya estaban justificados o tenían asistencia registrada.');
        }

        // Si hubo algunos conflictos o asistencias
        $warnings = [];
        if (count($conflictedDates) > 0) {
            $warnings[] = 'Los siguientes días ya tenían una justificación registrada y no se descontaron de vacaciones: ' . implode(', ', $conflictedDates);
        }
        if (count($attendanceDates) > 0) {
            $warnings[] = 'Los siguientes días ya tienen asistencia registrada y no se descontaron de vacaciones: ' . implode(', ', $attendanceDates);
        }

        if (count($warnings) > 0) {
            return redirect()->back()->with('warning', 'Vacaciones registradas parcialmente. ' . implode(' ', $warnings));
        }

        return redirect()->back()->with('success', 'Vacaciones registradas correctamente.');
    }

    public function add_eventuality(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'absenceReason' => 'required|string|max:255',
        ]);

        try {
            $file_numbers = staff::all()->pluck('file_number');
            $date = Carbon::parse($request->input('date'))->format('Y-m-d');
            $absenceReason_name = $request->input('absenceReason');
            $observations = $request->input('observations') . ' - ' . Auth::user()->name . ' ' . Carbon::now()->format('d/m/Y H:i');

            // Obtener o crear el motivo de inasistencia
            $absenceReason = absenceReason::firstOrCreate(
                ['name' => $absenceReason_name],
                ['logical_erase' => 1]
            );

            $vinieron = []; // empleados con asistencia ese día
            $agregados = 0; // contador de inasistencias agregadas

            foreach ($file_numbers as $file_number) {
                // Verificar si tiene una asistencia registrada ese día
                $tieneAsistencia = Attendance::where('file_number', $file_number)
                    ->whereDate('date', $date)
                    ->exists();

                if ($tieneAsistencia) {
                    $vinieron[] = $file_number;
                    continue; // no se justifica si ya vino
                }

                // Verificar si ya tiene una inasistencia justificada
                $inasistencia = NonAttendance::where('file_number', $file_number)
                    ->whereDate('date', $date)
                    ->first();

                if ($inasistencia) {
                    if ($inasistencia->absenceReason_id === null) {
                        // Actualizar la inasistencia sin justificar
                        $inasistencia->update([
                            'absenceReason_id' => $absenceReason->id,
                            'observations' => $observations,
                        ]);
                        $agregados++;
                    }
                    // Si ya tenía una justificada, no hacer nada
                } else {
                    // Crear nueva inasistencia justificada
                    NonAttendance::create([
                        'file_number' => $file_number,
                        'date' => $date,
                        'absenceReason_id' => $absenceReason->id,
                        'observations' => $observations,
                    ]);
                    $agregados++;
                }
            }

            if ($date >= Carbon::now()) {
                $mensaje = "Se justificaran las inasistencias del día " . Carbon::parse($date)->format('d/m/Y') . " con el motivo: {$absenceReason_name}";
            } else {
                $mensaje = "Se justificaron las inasistencias del día " . Carbon::parse($date)->format('d/m/Y') . " con el motivo: {$absenceReason_name}";
            }

            if (count($vinieron)) {
                $mensaje .= ', pero hay persona que tienen asistencias y no fueron afectadas.';
            }

            return redirect()->back()->with('success', $mensaje);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al procesar la inasistencia: ' . $e->getMessage());
        }
    }
}
