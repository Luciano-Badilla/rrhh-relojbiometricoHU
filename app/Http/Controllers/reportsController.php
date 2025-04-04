<?php

namespace App\Http\Controllers;

use App\Models\absenceReason;
use App\Models\area;
use App\Models\attendance;
use App\Models\attendance_reports;
use App\Models\clockLogs;
use App\Models\day;
use App\Models\devices;
use App\Models\NonAttendance;
use App\Models\NonAttendance_reports;
use App\Models\shift;
use App\Models\staff;
use App\Models\staff_area;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\DB;


class reportsController extends Controller
{
    public function nonAttendanceByAreaIndex(Request $request)
    {
        $areas = area::all();
        $absenceReasons = absenceReason::all();
        nonAttendance_reports::truncate();

        return view('reports.nonAttendanceByArea', [
            'absenceReasons' => $absenceReasons->sortBy('name'),
            'areas' => $areas->sortBy('name'),
            'nonAttendances' => session('nonAttendances'),  // Pasar la variable desde la sesión si es necesario
            'staffs' => session('staffs') ? collect(session('staffs'))->sortBy('name_surname') : collect(), // Recuperar staffs
            'area_selected' => session('area_selected') ?? null,
            'absenceReason_selected' => session('absenceReason_selected') ?? null,
            'dates' =>  session('dates') ?? null,
        ]);
    }

    public function nonAttendanceByAreaSearch(Request $request)
    {
        $date_range_checkbox = $request->input('date_range_checkbox');
        $area_id = $request->input('area_id');
        $absenceReason_id = $request->input('absenceReason_id');
        $area = area::find($area_id);
        $staffs = staff_area::where('area_id', $area_id)->with('staff')->get()->pluck('staff');
        $file_numbers = $staffs->where('marking', true)->pluck('file_number');
        $devices = devices::all();
        $areas = area::all();
        $absenceReasons = absenceReason::all();

        $devicesLogs = $this->getDeviceLogs($devices);

        foreach ($file_numbers as $file_number) {
            $this->processClockLogs($devicesLogs, $file_number);
        }

        if ($date_range_checkbox) {
            $date_from = Carbon::parse($request->input('date_from'));
            $date_to = Carbon::parse($request->input('date_to'));

            $clockLogs = clockLogs::whereDate('timestamp', '>=', $date_from)->whereDate('timestamp', '<=', $date_to)->get();
            $counter = 1;
            $lastFileNumber = null;

            $this->updateAttendanceFromClockLogs($clockLogs); //Crea las asistencias y las inasistencias

            foreach ($file_numbers as $file_number) {
                $this->createNonAttendance($file_number, $date_from, $date_to, null);
            }

            $nonAttendances = NonAttendance_reports::where('date', '>=', $date_from)
                ->where('date', '<=', $date_to)
                ->whereIn('file_number', $file_numbers)
                ->when(!is_null($absenceReason_id), function ($query) use ($absenceReason_id) {
                    return $query->whereIn('absenceReason_id', [$absenceReason_id]);
                })
                ->with('staff')
                ->orderBy('file_number', 'ASC')
                ->orderBy('date', 'ASC')
                ->get()
                ->map(function ($item) use (&$counter, &$lastFileNumber) {
                    if ($item->file_number !== $lastFileNumber && $lastFileNumber !== null) {
                        $counter = 1;
                        $item->counter = 1;
                        $counter++;
                    } elseif ($item->file_number === $lastFileNumber) {
                        $item->counter = $counter;
                        $counter++;
                    } else {
                        $item->counter = 1;
                        $counter++;
                    }
                    $lastFileNumber = $item->file_number;

                    $item->date_formated = Carbon::parse($item->date)->format('d/m/y');
                    $item->day = Carbon::createFromFormat('d/m/y', $item->date_formated)->locale('es')->translatedFormat('l');
                    $item->day = ucfirst($item->day);
                    $item->absenceReason = $item->absenceReason->name ?? null;

                    return $item;
                });
        } else {

            $counter = 1;
            $lastFileNumber = null;
            $date = $request->input('date');
            $clockLogs = clockLogs::whereDate('timestamp', '=', $date)->get();

            $this->updateAttendanceFromClockLogs($clockLogs); //Crea las asistencias y las inasistencias

            foreach ($file_numbers as $file_number) {
                $this->createNonAttendance($file_number, null, null, [$date]);
            }

            $nonAttendances = NonAttendance_reports::where('date', $date)->whereIn('file_number', $file_numbers)->when(!is_null($absenceReason_id), function ($query) use ($absenceReason_id) {
                return $query->whereIn('absenceReason_id', [$absenceReason_id]);
            })->orderBy('file_number', 'ASC')->orderBy('date', 'ASC')->get()->map(function ($item) use (&$counter, &$lastFileNumber) {

                if ($item->file_number !== $lastFileNumber && $lastFileNumber !== null) {
                    $counter = 1;
                    $item->counter = 1;
                    $counter++;
                } elseif ($item->file_number === $lastFileNumber) {
                    $item->counter = $counter;
                    $counter++;
                } else {
                    $item->counter = 1;
                    $counter++;
                }
                $lastFileNumber = $item->file_number;

                $item->date_formated = Carbon::parse($item->date)->format('d/m/y');
                $item->day = Carbon::createFromFormat('d/m/y', $item->date_formated)->locale('es')->translatedFormat('l');
                $item->day = ucfirst($item->day);
                $item->absenceReason = $item->absenceReason->name ?? null;

                return $item;
            });
        }


        return redirect()->route('reportView.nonAttendance')
            ->withInput()
            ->with([
                'nonAttendances' => $nonAttendances,
                'areas' => $areas->sortBy('name'),
                'absenceReasons' => $absenceReasons->sortBy('name'),
                'staffs' => $staffs->sortBy('name_surname'),
                'area_selected' => $area->name,
                'dates' => $date_range_checkbox ? 'Desde el ' . $date_from->format('d/m/y') . ' Hasta el ' . $date_to->format('d/m/y') : (Carbon::parse($date)->format('d/m/y') == Carbon::now()->format('d/m/y') ? Carbon::now()->format('d/m/y H:i') : Carbon::parse($date)->format('d/m/y'))
            ]);
    }

    public function nonAttendanceByAreaExport(PDF $pdf, Request $request)
    {
        $data = $request->all();

        // Habilitar el soporte para procesamiento de PHP en DomPDF
        $pdf->set_option('isPhpEnabled', true);

        // Cargar la vista y generar el PDF
        $pdfInstance = $pdf->loadView('pdf.nonAttendanceByArea', $data);

        return $pdfInstance->stream($request->file_name . '.pdf');
    }

    public function tardiesByAreaIndex(Request $request)
    {
        $areas = area::all();
        attendance_reports::truncate();

        return view('reports.tardiesByArea', [
            'areas' => $areas->sortBy('name'),
            'tardies' => session('tardies'),  // Pasar la variable desde la sesión si es necesario
            'staffs' => session('staffs') ? collect(session('staffs'))->sortBy('name_surname') : collect(), // Recuperar staffs
            'area_selected' => session('area_selected') ?? null,
            'tolerance' => session('tolerance') ?? null,
            'dates' =>  session('dates') ?? null,
        ]);
    }

    public function tardiesByAreaSearch(Request $request)
    {
        $date_range_checkbox = $request->input('date_range_checkbox');
        $tolerance = $request->input('tolerance');
        $area_id = $request->input('area_id');
        $area = area::find($area_id);
        $staffs = staff_area::where('area_id', $area_id)->with('staff')->get()->pluck('staff');
        $file_numbers = $staffs->where('marking', true)->pluck('file_number');
        $devices = devices::all();
        $areas = area::all();

        $devicesLogs = $this->getDeviceLogs($devices);

        foreach ($file_numbers as $file_number) {
            $this->processClockLogs($devicesLogs, $file_number);
        }

        if ($date_range_checkbox) {
            $date_from = Carbon::parse($request->input('date_from'));
            $date_to = Carbon::parse($request->input('date_to'));
            $counter = 1;
            $lastFileNumber = null;
            $clockLogs = clockLogs::whereDate('timestamp', '>=', $date_from)->whereDate('timestamp', '<=', $date_to)->get();

            $this->updateAttendanceFromClockLogs($clockLogs);

            $tardies = attendance_reports::whereBetween('attendance_reports.date', [$date_from, $date_to])
                ->whereIn('attendance_reports.file_number', $file_numbers)
                ->join('staff', 'staff.file_number', '=', 'attendance_reports.file_number')
                ->join('schedule_staff', 'schedule_staff.staff_id', '=', 'staff.id')
                ->join('schedule', function ($join) {
                    $join->on('schedule.id', '=', 'schedule_staff.schedule_id')
                        ->whereRaw('WEEKDAY(attendance_reports.date) + 1 = schedule.day_id');
                })
                ->join('shifts', 'shifts.id', '=', 'schedule.shift_id')
                ->select('attendance_reports.*', DB::raw('ANY_VALUE(shifts.startTime) as startTime'), DB::raw('ANY_VALUE(shifts.endTime) as endTime')) // ✅ Solución con ANY_VALUE()
                ->groupBy('attendance_reports.id') // ✅ Corregimos el GROUP BY
                ->orderBy('attendance_reports.file_number', 'ASC')
                ->orderBy('attendance_reports.date', 'ASC')
                ->get()
                ->filter(function ($item) use ($tolerance) { // 🔹 Pasamos la tolerancia
                    $startTime = $item->startTime ?? null;
                    $entryTime = $item->entryTime ?? null;

                    if (!$startTime || !$entryTime) {
                        return false;
                    }

                    // ✅ Sumamos la tolerancia a startTime
                    $allowedEntry = Carbon::parse($startTime)->addMinutes($tolerance);

                    return Carbon::parse($entryTime)->greaterThan($allowedEntry);
                })
                ->map(function ($item) use (&$counter, &$lastFileNumber) {
                    if ($item->file_number !== $lastFileNumber && $lastFileNumber !== null) {
                        $counter = 1;
                        $item->counter = 1;
                        $counter++;
                    } elseif ($item->file_number === $lastFileNumber) {
                        $item->counter = $counter;
                        $counter++;
                    } else {
                        $item->counter = 1;
                        $counter++;
                    }
                    $lastFileNumber = $item->file_number;

                    $item->date_formated = Carbon::parse($item->date)->format('d/m/y');
                    $item->day = Carbon::createFromFormat('d/m/y', $item->date_formated)->locale('es')->translatedFormat('l');
                    $item->day = ucfirst($item->day);
                    $item->asssignedSchedule = $item->startTime . ' - ' . $item->endTime;

                    return $item;
                });
        } else {
            $counter = 1;
            $lastFileNumber = null;
            $date = $request->input('date');

            $clockLogs = clockLogs::whereDate('timestamp', '=', $date)->get();

            $this->updateAttendanceFromClockLogs($clockLogs);
            foreach ($file_numbers as $file_number) {
                $this->createNonAttendance($file_number, null, null, [$date]);
            }


            $tardies = attendance_reports::where('date', $date)
                ->whereIn('attendance_reports.file_number', $file_numbers)
                ->join('staff', 'staff.file_number', '=', 'attendance_reports.file_number')
                ->join('schedule_staff', 'schedule_staff.staff_id', '=', 'staff.id')
                ->join('schedule', function ($join) {
                    $join->on('schedule.id', '=', 'schedule_staff.schedule_id')
                        ->whereRaw('WEEKDAY(attendance_reports.date) + 1 = schedule.day_id');
                })
                ->join('shifts', 'shifts.id', '=', 'schedule.shift_id')
                ->select(
                    'attendance_reports.*',
                    DB::raw('MIN(shifts.startTime) as startTime'),
                    DB::raw('MAX(shifts.endTime) as endTime')
                )
                ->groupBy(
                    'attendance_reports.id',
                    'attendance_reports.file_number',
                    'attendance_reports.date'
                )
                ->orderBy('attendance_reports.file_number', 'ASC')
                ->orderBy('attendance_reports.date', 'ASC')
                ->get()
                ->filter(function ($item) use ($tolerance) {
                    $startTime = $item->startTime ?? null;
                    $entryTime = $item->entryTime ?? null;

                    if (!$startTime || !$entryTime) {
                        return false;
                    }

                    $allowedEntry = Carbon::parse($startTime)->addMinutes($tolerance);

                    return Carbon::parse($entryTime)->greaterThan($allowedEntry);
                })
                ->map(function ($item) use (&$counter, &$lastFileNumber) {
                    if ($item->file_number !== $lastFileNumber && $lastFileNumber !== null) {
                        $counter = 1;
                        $item->counter = 1;
                        $counter++;
                    } elseif ($item->file_number === $lastFileNumber) {
                        $item->counter = $counter;
                        $counter++;
                    } else {
                        $item->counter = 1;
                        $counter++;
                    }
                    $lastFileNumber = $item->file_number;

                    $item->date_formated = Carbon::parse($item->date)->format('d/m/y');
                    $item->day = Carbon::createFromFormat('d/m/y', $item->date_formated)->locale('es')->translatedFormat('l');
                    $item->day = ucfirst($item->day);
                    $item->asssignedSchedule = $item->startTime . ' - ' . $item->endTime;

                    return $item;
                });
        }

        return redirect()->route('reportView.tardies')
            ->withInput()
            ->with([
                'tardies' => $tardies,
                'areas' => $areas->sortBy('name'),
                'staffs' => $staffs->sortBy('name_surname'),
                'area_selected' => $area->name,
                'tolerance' => $tolerance,
                'dates' => $date_range_checkbox ? 'Desde el ' . $date_from->format('d/m/y') . ' Hasta el ' . $date_to->format('d/m/y') : Carbon::parse($date)->format('d/m/y')
            ]);
    }

    public function tardiesByAreaExport(PDF $pdf, Request $request)
    {
        $data = $request->all();

        // Habilitar el soporte para procesamiento de PHP en DomPDF
        $pdf->set_option('isPhpEnabled', true);

        // Cargar la vista y generar el PDF
        $pdfInstance = $pdf->loadView('pdf.tardiesByArea', $data);

        return $pdfInstance->stream($request->file_name . '.pdf');
    }


    public function getDeviceLogs($devices)
    {
        $allLogs = [];

        foreach ($devices as $device) {
            try {
                $zk = new ZKTeco($device->ip, $device->port);

                if ($zk->connect()) {
                    $logs = $zk->getAttendance();

                    // Agregar identificador del dispositivo a cada log
                    foreach ($logs as &$log) {
                        $log['device_id'] = $device->id;
                    }

                    $allLogs = array_merge($allLogs, $logs);
                    $zk->disconnect();
                } else {
                    Log::error("No se pudo conectar al dispositivo con IP: {$device->ip}");
                }
            } catch (\Exception $e) {
                Log::error("Error al conectar con el dispositivo {$device->ip}: " . $e->getMessage());
            }
        }

        return $allLogs;
    }

    public function processClockLogs(array $logs, $file_number = null)
    {
        if ($file_number !== null) {
            $logs = array_filter($logs, fn($log) => isset($log['id']) && $log['id'] == $file_number);
        }

        if (empty($logs)) {
            return; // No hay logs que procesar
        }

        // Obtener los UIDs existentes en un solo query
        $existingUids = clockLogs::whereIn('uid', array_column($logs, 'uid'))->pluck('uid')->toArray();

        // Filtrar solo los logs que no existen
        $newLogs = array_filter($logs, fn($log) => !in_array($log['uid'], $existingUids));

        if (!empty($newLogs)) {
            // Preparar datos para inserción masiva
            $insertData = array_map(fn($log) => [
                'uid' => $log['uid'],
                'file_number' => $log['id'],
                'timestamp' => $log['timestamp'],
                'device_id' => $log['device_id'],
                'created_at' => now(),
                'updated_at' => now()
            ], $newLogs);

            // Insertar todos los nuevos registros de una sola vez
            clockLogs::insert($insertData);
        }
    }


    public function updateAttendanceFromClockLogs($logsQuery = null)
    {
        try {
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
                            $entries[] = $logsForDay[$i];
                        } else {
                            $exits[] = $logsForDay[$i];
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
            }
        } catch (\Exception $e) {
            Log::error("Error al actualizar la asistencia: {$e->getMessage()}");
            throw new \Exception("Hubo un problema al procesar los registros de asistencia.");
        }
    }

    public function createOrUpdateAttendance($entryLog, $exitLog = null)
    {
        $date = date('Y-m-d', strtotime($entryLog->timestamp));
        $entryTime = date('H:i:s', strtotime($entryLog->timestamp));
        $departureTime = $exitLog ? date('H:i:s', strtotime($exitLog->timestamp)) : null;

        // Obtener el día de la semana
        $dayName = ucfirst(Carbon::parse($date)->locale('es')->translatedFormat('l'));

        // Definir valor inicial de horas cumplidas
        $hoursCompleted = '00:00:00';

        if ($departureTime) {
            // Calcular horas trabajadas
            $workedSeconds = Carbon::createFromFormat('H:i:s', $entryTime)
                ->diffInSeconds(Carbon::createFromFormat('H:i:s', $departureTime));
            $hoursCompleted = gmdate('H:i:s', $workedSeconds);
        }

        // Verificar si ya existe un registro con la misma entrada
        $attendances = Attendance::where('file_number', $entryLog->file_number)
            ->where('date', $date)
            ->get();

        $existingAttendance = $attendances->firstWhere('entryTime', $entryTime);

        if (!$existingAttendance) {
            // Crear un nuevo registro
            attendance_reports::create([
                'file_number' => $entryLog->file_number,
                'date' => $date,
                'entryTime' => $entryTime,
                'departureTime' => $departureTime,
                'hoursCompleted' => $hoursCompleted,
                'day' => $dayName,
                'observations' => null,
            ]);
        }
    }

    public function createNonAttendance($file_number, $date_from = null, $date_to = null, $specific_dates = [])
    {
        $clockLogsController = new clockLogsController();
        $staff = Staff::where('file_number', $file_number)->first();

        $existingReports = NonAttendance_reports::where('file_number', $staff->file_number)
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        // Si se proporcionan fechas específicas, se usan esas
        if (!empty($specific_dates)) {
            // Filtrar las asistencias por las fechas específicas
            $attendances = clockLogs::where('file_number', $file_number)
                ->whereIn(DB::raw('DATE(timestamp)'), $specific_dates)
                ->pluck('timestamp')
                ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
                ->toArray();

            // Determinar los años y meses relevantes de las fechas específicas
            $years = collect($specific_dates)->map(fn($d) => Carbon::parse($d)->year)->unique()->toArray();
            $months = collect($specific_dates)->map(fn($d) => Carbon::parse($d)->month)->unique()->toArray();
        } elseif ($date_from && $date_to) {
            // Si se proporciona un rango de fechas, se usan esas fechas
            $attendances = clockLogs::where('file_number', $file_number)
                ->whereBetween(DB::raw('DATE(timestamp)'), [$date_from, $date_to])
                ->pluck('timestamp')
                ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
                ->toArray();

            // Determinar los años y meses relevantes del rango
            $years = range(Carbon::parse($date_from)->year, Carbon::parse($date_to)->year);
            $months = range(Carbon::parse($date_from)->month, Carbon::parse($date_to)->month);
        }

        // Obtener inasistencias ya registradas con sus datos completos
        $nonAttendances = NonAttendance::where('file_number', $staff->file_number)
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d')); // clave por fecha en formato Y-m-d

        // Inicializamos el array para las inasistencias a insertar
        $bulkInsert = [];
        $addedDates = []; // Para controlar duplicados por fecha

        foreach ($years as $year) {
            foreach ($months as $month) {
                $workingDays = collect($clockLogsController->getWorkingDays($staff->id, $month, $year))
                    ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));

                foreach ($workingDays as $workingDayFormatted) {
                    if (in_array($workingDayFormatted, $attendances)) {
                        continue; // No duplicamos si fue asistencia
                    }

                    $actualDate = Carbon::now()->format('Y-m-d');

                    if ($actualDate >= $workingDayFormatted) {
                        if (!in_array($workingDayFormatted, $addedDates)) {
                            $bulkInsert[] = [
                                'file_number' => $staff->file_number,
                                'date' => $workingDayFormatted,
                                'absenceReason_id' => $nonAttendances[$workingDayFormatted]->absenceReason_id ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $addedDates[] = $workingDayFormatted; // Marcamos como ya insertada
                        }
                    }
                }
            }
        }


        // Insertar todas las inasistencias de una sola vez
        if (!empty($bulkInsert)) {
            NonAttendance_reports::insert($bulkInsert);
        }
    }
}
