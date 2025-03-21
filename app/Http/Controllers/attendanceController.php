<?php

namespace App\Http\Controllers;

use App\Models\attendance;
use App\Models\clockLogs;
use App\Models\day;
use App\Models\NonAttendance;
use App\Models\schedule_staff;
use App\Models\shift;
use App\Models\staff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

    public function add_manual(request $request)
    {
        $staff_id = $request->input('staff_id');
        $staff = Staff::find($staff_id);

        $date = $request->input('attendance_date');
        $entryTime = $request->input('entryTime');
        $departureTime = $request->input('departureTime');
        $observations = $request->input('observations');

        if (Carbon::parse($entryTime) > Carbon::parse($departureTime)) {
            return redirect()->back()->with('error', 'La entrada no puede ser despues de la salida');
        }

        if ($date > Carbon::now()) {
            return redirect()->back()->with('error', 'La fecha no puede ser mayor a la fecha actual');
        }

        $minuteTolerance = 60;

        $sameDayRecords = Attendance::where('file_number', $staff->file_number)
            ->whereDate('date', $date)
            ->get();

        foreach ($sameDayRecords as $record) {
            if ($record->entryTime) {
                $existingEntryTime = Carbon::parse($record->entryTime);
                if (abs($existingEntryTime->diffInMinutes($entryTime)) < $minuteTolerance) {
                    return redirect()->back()->with('error', 'Ya existe una asistencia el dia ' . date('d/m/y', strtotime($date)) . ' con unos valores muy proximos a los ingresados');
                }
            }

            if ($record->departureTime && $departureTime) {
                $existingDepartureTime = Carbon::parse($record->departureTime);
                if (abs($existingDepartureTime->diffInMinutes($departureTime)) < $minuteTolerance) {
                    return redirect()->back()->with('error', 'Ya existe una asistencia el dia ' . date('d/m/y', strtotime($date)) . ' con unos valores muy proximos a los ingresados');
                }
            }
        }

        if ($entryTime) {
            ClockLogs::create([
                'file_number' => $staff->file_number,
                'timestamp' => date('Y-m-d H:i:s', strtotime($date . ' ' . $entryTime))
            ]);
        }

        if ($departureTime) {
            ClockLogs::create([
                'file_number' => $staff->file_number,
                'timestamp' => date('Y-m-d H:i:s', strtotime($date . ' ' . $departureTime))
            ]);
        }

        if ($entryTime || $departureTime) {
            NonAttendance::where('file_number', $staff->file_number)->where('date', $date)->delete();
        }

        if ($entryTime && $departureTime) {
            $dayName = ucfirst(Carbon::parse($date)->locale('es')->translatedFormat('l'));
            $schedule = $staff->schedules->firstWhere('day_id', day::where('name', $dayName)->value('id'));

            // Definir valores iniciales
            $hoursCompleted = '00:00:00';
            $extraHours = '00:00:00';

            // Obtener todas las asistencias del día
            $attendances = attendance::where('file_number', $staff->file_number)
                ->where('date', $date)
                ->get();

            // Calcular las horas totales trabajadas hasta el momento
            $totalWorkedSeconds = 0;
            foreach ($attendances as $attendance) {
                $totalWorkedSeconds += Carbon::createFromFormat('H:i:s', $attendance->hoursCompleted)->diffInSeconds('00:00:00');
            }



            if ($schedule && $departureTime) {
                // Calcular horas trabajadas en este registro
                $ClockLogsController = new ClockLogsController();
                $workedHours = $ClockLogsController->calculateWorkedHours($entryTime, $departureTime);
                $workedSeconds = Carbon::createFromFormat('H:i:s', $workedHours)->diffInSeconds('00:00:00');

                $hoursCompleted = gmdate('H:i:s', $workedSeconds);

                // Obtener el total de registros
                $totalRecords = ClockLogs::where('file_number', $staff->file_number)
                    ->whereDate('timestamp', $date)
                    ->count();

                $attendanceCount = max(1, ceil($totalRecords / 2));

                // Obtener las horas requeridas por el turno
                $shift = shift::find($schedule->shift_id);
                if ($shift) {
                    $startTime = Carbon::createFromFormat('H:i:s', $shift->startTime);
                    $endTime = Carbon::createFromFormat('H:i:s', $shift->endTime);
                    $hoursRequiredInSeconds = $startTime->diffInSeconds($endTime);

                    if ($totalWorkedSeconds > $hoursRequiredInSeconds) {
                        $requiredPerRecord = $hoursRequiredInSeconds / $attendanceCount;
                    } else {
                        $requiredPerRecord = $hoursRequiredInSeconds;
                    }

                    // Si este registro supera su parte correspondiente, calcular horas extra
                    if ($workedSeconds > $requiredPerRecord) {
                        $extraSeconds = $workedSeconds - $requiredPerRecord;

                        // Ajustar las horas extras en bloques de 15 minutos (900 segundos)
                        $adjustedExtraSeconds = floor($extraSeconds / 900) * 900;

                        $extraHours = gmdate('H:i:s', $adjustedExtraSeconds);
                    }
                }
            }

            attendance::create([
                'file_number' => $staff->file_number,
                'date' => $date,
                'entryTime' => $entryTime,
                'departureTime' => $departureTime,
                'hoursCompleted' => $hoursCompleted,

                'extraHours' => $extraHours,
                'day' => $dayName,
                'observations' => $observations

            ]);
        }

        return redirect()->back()->with('success', 'Asistencia agregada correctamente para el dia ' . date('d/m/y', strtotime($date)));
    }

    public function add_many_nonattendance(request $request)
    {
        $attendance_date_from = Carbon::parse($request->input('attendance_date_from'));
        $attendance_date_to = Carbon::parse($request->input('attendance_date_to'));
        $staff_id = $request->input('staff_id');
        $staff = staff::find($staff_id);
        $absenceReason = $request->input('absenceReason');
        $observations = $request->input('observations');

        // Verificar si la fecha de inicio es mayor que la fecha de fin
        if ($attendance_date_from > $attendance_date_to) {
            return redirect()->back()->with('error', 'La fecha desde: ' . $attendance_date_from->format('d/m/y') . ' no puede ser mayor a la fecha hasta: ' . $attendance_date_to->format('d/m/y'));
        }

        // Generar años entre la fecha de inicio y fin
        $years = range($attendance_date_from->year, $attendance_date_to->year);

        // Generar meses entre la fecha de inicio y fin
        $months = [];
        foreach ($years as $year) {
            $startMonth = ($year == $attendance_date_from->year) ? $attendance_date_from->month : 1;
            $endMonth = ($year == $attendance_date_to->year) ? $attendance_date_to->month : 12;
            $months[$year] = range($startMonth, $endMonth);
        }

        $missingDates = []; // Fechas donde NO se pudo registrar una inasistencia

        foreach ($years as $year) {
            foreach ($months[$year] as $month) {
                // Obtener los días laborales para el mes y año actuales
                $workingDays = $this->getWorkingDays($staff->id, $month, $year);

                foreach ($workingDays as $workingDay) {
                    $workingDay = Carbon::parse($workingDay);

                    // Verificar si el día laboral está dentro del rango de fechas
                    if ($workingDay->between($attendance_date_from, $attendance_date_to, true)) {
                        $workingDayFormatted = $workingDay->format('Y-m-d');

                        // Obtener todas las asistencias del mes para el staff
                        $attendances = clockLogs::where('file_number', $staff->file_number)
                            ->whereMonth('timestamp', $month)
                            ->whereYear('timestamp', $year)
                            ->pluck('timestamp')
                            ->map(fn($timestamp) => Carbon::parse($timestamp)->toDateString())
                            ->toArray();

                        // Verificar si ya existe una asistencia o una inasistencia para este día
                        $attendanceExists = in_array($workingDayFormatted, $attendances);
                        $nonAttendanceExist = NonAttendance::where([
                            ['file_number', '=', $staff->file_number],
                            ['date', '=', $workingDayFormatted]
                        ])->exists();

                        if ($attendanceExists || $nonAttendanceExist) {
                            // Si ya existe una asistencia o inasistencia, agregar la fecha a las faltantes
                            $missingDates[] = $workingDayFormatted;
                        } else {
                            // Si no existe, registrar la inasistencia
                            NonAttendance::create([
                                'file_number' => $staff->file_number,
                                'date' => $workingDayFormatted,
                                'absenceReason_id' => $absenceReason,
                                'observations' => $observations,
                            ]);
                        }
                    }
                }
            }
        }

        // Mostrar las fechas donde no se pudo registrar la inasistencia
        if (!empty($missingDates)) {
            $missingDatesStr = implode(', ', $missingDates);
            return redirect()->back()->with('warning', 'Algunas fechas no pudieron ser registradas porque ya existían: ' . $missingDatesStr);
        } else {
            return redirect()->back()->with('success', 'Inasistencias guardadas correctamente. Todas las fechas fueron registradas.');
        }
    }


    public function getWorkingDays($staffId, $month, $year)
    {
        Carbon::setLocale('es');
        // Establecer la fecha de hoy en el mes y año proporcionado
        $today = Carbon::create($year, $month, 1)->endOfMonth();  // Último día del mes
        $startOfMonth = Carbon::create($year, $month, 1);  // Primer día del mes

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
        for ($date = $startOfMonth; $date <= $today; $date->addDay()) {
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
