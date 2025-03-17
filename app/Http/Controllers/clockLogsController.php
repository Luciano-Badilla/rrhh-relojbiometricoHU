<?php

namespace App\Http\Controllers;

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
        $devices = [
            ['ip' => '172.22.112.220', 'port' => 4370, 'device_id' => 1], // Hall Principal
            ['ip' => '172.22.112.221', 'port' => 4370, 'device_id' => 2]  // Subsuelo
        ];

        foreach ($devices as $device) {
            try {
                // Crear instancia de ZKTeco
                $zk = new ZKTeco($device['ip'], $device['port']);

                // Conectar al dispositivo
                if ($zk->connect()) {
                    // Obtener registros de asistencia del dispositivo
                    $logs = $zk->getAttendance();

                    foreach ($logs as $log) {

                        // Verificar si el log ya existe en clockLogs
                        $exists = clockLogs::where('uid', $log['uid'])->exists();

                        if (!$exists) {
                            // Guardar el log si no existe
                            clockLogs::create([
                                'uid' => $log['uid'],
                                'file_number' => $log['id'],
                                'timestamp' => $log['timestamp'],
                                'device_id' => $device['device_id']
                            ]);
                        }
                    }

                    // Desconectar del dispositivo
                    $zk->disconnect();
                } else {
                    Log::error("No se pudo conectar al dispositivo con IP: {$device['ip']}");
                    return response()->json(['error' => "No se pudo conectar al dispositivo con IP: {$device['ip']}"], 500);
                }
            } catch (\Exception $e) {
                Log::error("Error al procesar el dispositivo con IP: {$device['ip']} - Error: {$e->getMessage()}");
                return response()->json(['error' => 'Hubo un problema al obtener los datos del dispositivo.'], 500);
            }
        }

        return response()->json(['message' => 'Backup realizado correctamente.']);
    }

    public function update_attendance($file_number = null)
    {

        $devices = [
            ['ip' => '172.22.112.220', 'port' => 4370, 'device_id' => 1],
            ['ip' => '172.22.112.221', 'port' => 4370, 'device_id' => 2]
        ];

        foreach ($devices as $device) {
            try {
                $zk = new ZKTeco($device['ip'], $device['port']);

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

                        if (!$exists) {
                            clockLogs::create([
                                'uid' => $log['uid'],
                                'file_number' => $log['id'],
                                'timestamp' => $log['timestamp'],
                                'device_id' => $device['device_id']
                            ]);
                        }
                    }

                    $zk->disconnect();
                } else {
                    Log::error("No se pudo conectar al dispositivo con IP: {$device['ip']}");
                    return response()->json(['error' => "No se pudo conectar al dispositivo con IP: {$device['ip']}"], 500);
                }
            } catch (\Exception $e) {
                Log::error("Error al procesar el dispositivo con IP: {$device['ip']} - Error: {$e->getMessage()}");
                return response()->json(['error' => 'Hubo un problema al obtener los datos del dispositivo.'], 500);
            }
        }

        $this->updateAttendanceFromClockLogs($file_number);

        return response()->json(['message' => 'Los registros de asistencia se actualizaron correctamente.']);
    }


    public function updateAttendanceFromClockLogs($fileNumber = null)
    {
        try {
            // Si se pasa un file_number, filtrar los registros por ese número
            $logsQuery = clockLogs::orderBy('timestamp');

            if ($fileNumber) {
                $logsQuery->where('file_number', $fileNumber);  // Filtra por el file_number
            }

            $logsGroupedByUser = $logsQuery->get()->groupBy('file_number'); // Agrupar por empleado

            foreach ($logsGroupedByUser as $fileNumber => $logs) {
                // Procesar los logs ordenados
                $logs = $logs->sortBy('timestamp')->values();

                for ($i = 0; $i < $logs->count(); $i++) {
                    $entryLog = $logs[$i];
                    $exitLog = $logs[$i + 1] ?? null; // El siguiente registro podría ser la salida

                    // Si no hay un registro de salida o no está en el mismo día, usar el mismo horario para entrada y salida
                    if (!$exitLog || date('Y-m-d', strtotime($entryLog->timestamp)) !== date('Y-m-d', strtotime($exitLog->timestamp))) {
                        $this->createOrUpdateAttendance($entryLog, $entryLog); // Mismo horario como entrada y salida
                        continue;
                    }

                    // Si hay entrada y salida en el mismo día, procesarlas como un par
                    $this->createOrUpdateAttendance($entryLog, $exitLog);
                    // Saltar al siguiente par
                    $i++;
                }
            }

            $this->createNonAttendance($entryLog->file_number);
            
            Staff::where('file_number', $entryLog->file_number)->update([
                'last_checked' => Carbon::now()->toDateString()
            ]);
        } catch (\Exception $e) {
            Log::error("Error al actualizar la asistencia: {$e->getMessage()}");
            throw new \Exception("Hubo un problema al procesar los registros de asistencia.");
        }
    }


    private function createOrUpdateAttendance($entryLog, $exitLog = null)
    {
        // Obtener la fecha del registro
        $date = date('Y-m-d', strtotime($entryLog->timestamp));
        $entryTime = date('H:i:s', strtotime($entryLog->timestamp));
        $departureTime = $exitLog ? date('H:i:s', strtotime($exitLog->timestamp)) : null;

        // Buscar el horario del día correspondiente
        $staff = staff::where('file_number', $entryLog->file_number)->first();
        $lastChecked = $staff->last_checked;
        $dayName = ucfirst(Carbon::parse($date)->locale('es')->translatedFormat('l')); // Nombre del día
        $schedule = $staff->schedules->firstWhere('day_id', day::where('name', $dayName)->value('id'));

        // Definir valores iniciales
        $hoursCompleted = '00:00:00';
        $extraHours = '00:00:00';

        if ($schedule && $departureTime) {
            // Calcular horas trabajadas
            $hoursCompleted = $this->calculateWorkedHours($entryTime, $departureTime);

            // Calcular horas extras
            $shift = shift::find($schedule->shift_id);
            if ($shift) {
                $startTime = Carbon::createFromFormat('H:i:s', $shift->startTime);
                $endTime = Carbon::createFromFormat('H:i:s', $shift->endTime);
                $hoursRequiredInSeconds = $startTime->diffInSeconds($endTime);

                $workedSeconds = Carbon::createFromFormat('H:i:s', $hoursCompleted)->diffInSeconds('00:00:00');
                if ($workedSeconds > $hoursRequiredInSeconds) {
                    $extraHours = gmdate('H:i:s', $workedSeconds - $hoursRequiredInSeconds);
                }
            }
        }

        // Buscar asistencia existente
        $attendance = attendance::where('file_number', $entryLog->file_number)
            ->where('date', $date)
            ->first();

        if ($attendance) {
            if ($attendance->date >= $lastChecked) {
                $attendance->update([
                    'entryTime' => min($attendance->entryTime, $entryTime),
                    'departureTime' => $departureTime ?? $attendance->departureTime,
                    'hoursCompleted' => $hoursCompleted,
                    'extraHours' => $extraHours,
                ]);
            }
        } else {
            // Crear nueva asistencia si no existe
            attendance::create([
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

    private function createNonAttendance($file_number)
    {
        $staff = Staff::where('file_number', $file_number)->first();
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
