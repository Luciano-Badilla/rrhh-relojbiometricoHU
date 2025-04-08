<?php

namespace App\Http\Controllers;

use App\Models\devices;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Http\Request;
use App\Models\clockLogs;
use App\Models\attendance;
use App\Models\day;
use App\Models\NonAttendance;
use App\Models\schedule_staff;
use App\Models\shift;
use App\Models\staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class clockLogsController extends Controller
{

    public function backup()
    {
        // Datos de los dispositivos ZKTeco
        $devices = devices::all();

        foreach ($devices as $device) {
            try {
                // Crear instancia de ZKTeco
                $zk = new ZKTeco($device->ip, $device->port);

                // Conectar al dispositivo
                if ($zk->connect()) {
                    // Obtener registros de asistencia del dispositivo
                    $logs = $zk->getAttendance();

                    foreach ($logs as $log) {

                        // Verificar si el log ya existe en clockLogs
                        $exists = clockLogs::where('uid', $log['uid'])->exists();
                        $staff = staff::where('file_number', $log['id'])->first();

                        if (!$exists) {
                            // Guardar el log si no existe
                            clockLogs::create([
                                'uid' => $log['uid'],
                                'file_number' => $log['id'],
                                'timestamp' => $log['timestamp'],
                                'device_id' => $device->id,
                                'marking' => $staff?->marking ? $staff?->marking : true,
                                'inactive' => $staff?->inactive_since ? true : false
                            ]);
                        }
                    }

                    // Desconectar del dispositivo
                    $zk->disconnect();
                } else {
                    Log::error("No se pudo conectar al dispositivo con IP: {$device->ip}");
                    return response()->json(['error' => "No se pudo conectar al dispositivo con IP: {$device->ip}"], 500);
                }
            } catch (\Exception $e) {
                Log::error("Error al procesar el dispositivo con IP: {$device->ip} - Error: {$e->getMessage()}");
                return response()->json(['error' => 'Hubo un problema al obtener los datos del dispositivo.'], 500);
            }
        }

        return response()->json(['message' => 'Backup realizado correctamente.']);
    }

    public function update_attendance($file_number = null)
    {

        $devices = devices::all();

        foreach ($devices as $device) {
            try {
                $zk = new ZKTeco($device->ip, $device->port);

                if ($zk->connect()) {
                    $logs = $zk->getAttendance();


                    // Filtrar por file_number si está definido
                    if ($file_number !== null) {
                        $logs = array_filter($logs, function ($log) use ($file_number) {
                            return isset($log['id']) && $log['id'] == $file_number;
                        });
                    }

                    foreach ($logs as $log) {
                        $exists = clockLogs::where('uid', $log['uid'])->exists();
                        $staff = staff::where('file_number', $log['id'])->first();

                        if (!$exists) {
                            // Guardar el log si no existe
                            clockLogs::create([
                                'uid' => $log['uid'],
                                'file_number' => $log['id'],
                                'timestamp' => $log['timestamp'],
                                'device_id' => $device->id,
                                'marking' => $staff?->marking ? $staff?->marking : true,
                                'inactive' => $staff?->inactive_since ? true : false
                            ]);
                        }
                    }

                    $zk->disconnect();
                } else {
                    Log::error("No se pudo conectar al dispositivo con IP: {$device->ip}");
                    return response()->json(['error' => "No se pudo conectar al dispositivo con IP: {$device->ip}"], 500);
                }
            } catch (\Exception $e) {
                Log::error("Error al procesar el dispositivo con IP: {$device->ip} - Error: {$e->getMessage()}");
                return response()->json(['error' => 'Hubo un problema al obtener los datos del dispositivo.'], 500);
            }
        }

        $this->updateAttendanceFromClockLogs($file_number);

        return response()->json(['message' => 'Los registros de asistencia se actualizaron correctamente.']);
    }


    public function updateAttendanceFromClockLogs($fileNumber = null)
    {
        try {
            $logsQuery = clockLogs::where('file_number', $fileNumber)->orderBy('timestamp')->get();

            $logsGroupedByUser = $logsQuery->groupBy('file_number');

            foreach ($logsGroupedByUser as $fileNumber => $logs) {
                $logsGroupedByDate = $logs->groupBy(function ($log) {
                    return date('Y-m-d', strtotime($log->timestamp));
                });

                foreach ($logsGroupedByDate as $date => $logsForDay) {
                    $logsForDay = $logsForDay->sortBy('timestamp')->values();

                    $entries = [];
                    $exits = [];

                    // Separar entradas y salidas
                    for ($i = 0; $i < $logsForDay->count(); $i++) {
                        if ($i % 2 == 0) {
                            if ($logsForDay[$i]['marking'] && $logsForDay[$i]['inactive'] == 0) {
                                $entries[] = $logsForDay[$i];
                            }
                        } else {
                            if ($logsForDay[$i]['marking'] && $logsForDay[$i]['inactive'] == 0) {
                                $exits[] = $logsForDay[$i];
                            }
                        }
                    }

                    // Asegurar que cada entrada tenga una salida correspondiente
                    for ($i = 0; $i < count($entries); $i++) {
                        $entry = $entries[$i] ?? null;
                        $exit = $exits[$i] ?? null;

                        if ($entry && $exit) {
                            // Registrar asistencia con entrada y salida
                            $this->createOrUpdateAttendance($entry, $exit);
                        } elseif ($entry && !$exit) {
                            // Si hay una entrada sin salida, registrar con la misma hora
                            $this->createOrUpdateAttendance($entry, $entry);
                        }
                    }
                }

                // Verificar inasistencias después de registrar asistencias
                $this->createNonAttendance($fileNumber);

                Staff::where('file_number', $fileNumber)->update(['last_checked' => Carbon::now()->toDateString()]);
            }
        } catch (\Exception $e) {
            Log::error("Error al actualizar la asistencia: {$e->getMessage()}");
            throw new \Exception("Hubo un problema al procesar los registros de asistencia.");
        }
    }



    public function createOrUpdateAttendance($entryLog, $exitLog = null)
    {
        $staff = Staff::where('file_number', $entryLog->file_number)->first();
        if ($staff->marking && $staff->inactive_since == null) {
            $date = date('Y-m-d', strtotime($entryLog->timestamp));
            $entryTime = date('H:i:s', strtotime($entryLog->timestamp));
            $departureTime = $exitLog ? date('H:i:s', strtotime($exitLog->timestamp)) : null;

            // Buscar el horario del día correspondiente
            $lastChecked = $staff->last_checked;
            $dayName = ucfirst(Carbon::parse($date)->locale('es')->translatedFormat('l'));
            $schedule = $staff->schedules->firstWhere('day_id', Day::where('name', $dayName)->value('id'));

            // Definir valores iniciales
            $hoursCompleted = '00:00:00';
            $extraHours = '00:00:00';

            // Obtener todas las asistencias del día
            $attendances = Attendance::where('file_number', $entryLog->file_number)
                ->where('date', $date)
                ->get();

            // Calcular el total de horas trabajadas hasta el momento
            $totalWorkedSeconds = 0;
            foreach ($attendances as $attendance) {
                $totalWorkedSeconds += Carbon::createFromFormat('H:i:s', $attendance->hoursCompleted)->diffInSeconds('00:00:00');
            }

            if ($schedule && $departureTime) {
                // Obtener el turno
                $shift = Shift::find($schedule->shift_id);
                if ($shift) {
                    $startTime = Carbon::createFromFormat('H:i:s', $shift->startTime);
                    $endTime = Carbon::createFromFormat('H:i:s', $shift->endTime);
                    $hoursRequiredInSeconds = $startTime->diffInSeconds($endTime);

                    // Asegurar que la entrada no sea antes del turno
                    $adjustedEntryTime = Carbon::createFromFormat('H:i:s', $entryTime);
                    if ($adjustedEntryTime->lessThan($startTime)) {
                        $adjustedEntryTime = $startTime;
                    }

                    // Calcular horas trabajadas desde la hora ajustada
                    $workedSeconds = $adjustedEntryTime->diffInSeconds(Carbon::createFromFormat('H:i:s', $departureTime));
                    $hoursCompleted = gmdate('H:i:s', $workedSeconds);

                    // Obtener el total de registros (pares de entrada y salida)
                    $totalRecords = ClockLogs::where('file_number', $entryLog->file_number)
                        ->whereDate('timestamp', $date)
                        ->count();
                    $attendanceCount = max(1, ceil($totalRecords / 2));

                    // Calcular las horas requeridas por registro
                    $requiredPerRecord = $hoursRequiredInSeconds / $attendanceCount;

                    // Si se superan las horas requeridas, calcular horas extra
                    if ($totalWorkedSeconds + $workedSeconds > $hoursRequiredInSeconds) {
                        $extraSeconds = ($totalWorkedSeconds + $workedSeconds) - $hoursRequiredInSeconds;

                        // Ajustar las horas extras en bloques de 15 minutos (900 segundos)
                        $adjustedExtraSeconds = floor($extraSeconds / 900) * 900;
                        $extraHours = gmdate('H:i:s', $adjustedExtraSeconds);
                    }
                }
            }

            // Verificar si ya existe un registro con la misma entrada
            $existingAttendance = $attendances->firstWhere('entryTime', $entryTime);

            if ($existingAttendance) {
                if ($existingAttendance->date >= $lastChecked) {
                    // Actualizar el registro existente
                    $existingAttendance->update([
                        'departureTime' => $departureTime ?? $existingAttendance->departureTime,
                        'hoursCompleted' => $hoursCompleted,
                        'extraHours' => $extraHours,
                    ]);
                }
            } else {
                // Crear un nuevo registro si no existe uno igual
                Attendance::create([
                    'file_number' => $entryLog->file_number,
                    'date' => $date,
                    'entryTime' => $entryTime,
                    'departureTime' => $departureTime,
                    'hoursCompleted' => $hoursCompleted,
                    'extraHours' => $extraHours,
                    'day' => $dayName,
                    'observations' => null,
                ]);
            }
        }
    }

    public function createNonAttendance($file_number)
    {
        $staff = Staff::where('file_number', $file_number)->first();
        if ($staff->marking && $staff->inactive_since == null) {
            $years = ClockLogs::all()->pluck('timestamp')->map(fn($timestamp) => Carbon::parse($timestamp)->year)->unique()->sortDesc()->values();
            $lastChecked = $staff->last_checked;
            $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

            foreach ($years as $year) {
                foreach ($months as $month) {
                    $workingDays = $this->getWorkingDays($staff->id, $month, $year);

                    foreach ($workingDays as $workingDay) {
                        $workingDay = Carbon::parse($workingDay)->format('Y-m-d'); // Formatear el día laboral

                        // Si la fecha ya fue revisada, se omite
                        if ($lastChecked && $workingDay <= $lastChecked) {
                            continue;
                        }

                        // Obtener todas las asistencias del mes para el staff
                        $attendances = clockLogs::where('file_number', $file_number)
                            ->whereMonth('timestamp', $month)
                            ->whereYear('timestamp', $year)
                            ->pluck('timestamp') // Solo obtener las fechas
                            ->map(function ($timestamp) {
                                return Carbon::parse($timestamp)->toDateString(); // Formato yyyy-mm-dd
                            })
                            ->toArray();

                        // Verificar si ya existe una asistencia o una inasistencia para este día
                        $attendanceExists = in_array($workingDay, $attendances);
                        $nonAttendanceExist = NonAttendance::where([
                            ['file_number', '=', $staff->file_number],
                            ['date', '=', $workingDay]
                        ])->exists();

                        if (!$attendanceExists && !$nonAttendanceExist) {
                            $actualDate = Carbon::now()->format('Y-m-d');
                            $dateToCompare = Carbon::createFromDate($year, $month, Carbon::now()->day)->format('Y-m-d');

                            if ($workingDay != $actualDate && $actualDate >= $dateToCompare) {
                                NonAttendance::create([
                                    'file_number' => $staff->file_number,
                                    'date' => $workingDay,
                                ]);
                            }
                        }
                    }
                }
            }
        }
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
