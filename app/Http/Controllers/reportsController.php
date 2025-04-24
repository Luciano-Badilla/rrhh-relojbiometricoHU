<?php

namespace App\Http\Controllers;

use App\Models\absenceReason;
use App\Models\area;
use App\Models\attendance;
use App\Models\attendance_reports;
use App\Models\category;
use App\Models\clockLogs;
use App\Models\coordinator;
use App\Models\day;
use App\Models\devices;
use App\Models\NonAttendance;
use App\Models\NonAttendance_reports;
use App\Models\secretary;
use App\Models\shift;
use App\Models\staff;
use App\Models\staff_area;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use FontLib\Table\Type\name;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class reportsController extends Controller
{
    public function nonAttendanceByAreaIndex(Request $request)
    {
        $areas = area::all();
        $absenceReasons = absenceReason::all();
        $secretaries = secretary::all();
        $worker_status = ['Planta', 'Contratado'];
        nonAttendance_reports::truncate();

        return view('reports.nonAttendanceByArea', [
            'absenceReasons' => $absenceReasons->sortBy('name'),
            'areas' => $areas->sortBy('name'),
            'nonAttendances' => session('nonAttendances'),  // Pasar la variable desde la sesión si es necesario
            'staffs' => session('staffs') ? collect(session('staffs'))->sortBy('name_surname') : collect(), // Recuperar staffs
            'area_selected' => session('area_selected') ?? null,
            'absenceReason_selected' => session('absenceReason_selected') ?? null,
            'dates' =>  session('dates') ?? null,
            'secretaries' => $secretaries->sortBy('name'),
            'worker_status' => $worker_status,
            'staffsGrouped' => session('staffsGrouped') ?? null,

        ]);
    }

    public function nonAttendanceByAreaSearch(Request $request)
    {
        $date_range_checkbox = $request->input('date_range_checkbox');
        $area_id = $request->input('area_id');
        $absenceReason_id = $request->input('absenceReason_id');
        $secretary_id = $request->input('secretary_id');
        $area = area::find($area_id);
        $worker_status_input = ($request->input('worker_status') != "") ? strtolower($request->input('worker_status')) : null;

        $staffAreasQuery = staff_area::with('staff');
        if (!is_null($area_id)) {
            $staffAreasQuery->where('area_id', $area_id);
        }
        $staffAreas = $staffAreasQuery->get();

        $staffsGroupedByArea = $staffAreas->groupBy('area_id')->map(function ($group) use ($secretary_id, $worker_status_input) {
            return $group->pluck('staff')
                ->filter() // Elimina null
                ->filter(function ($staff) use ($secretary_id, $worker_status_input) {
                    return $staff->marking &&
                        (is_null($secretary_id) || $staff->secretary_id == $secretary_id) &&
                        (is_null($worker_status_input) || strtolower($staff->worker_status) == $worker_status_input);
                });
        })->filter(function ($staffs) {
            return $staffs->isNotEmpty(); // Oculta áreas vacías
        })->sortKeys()->sortBy(function ($staffs, $area_id) {
            return area::find($area_id)?->name ?? '';
        });

        $file_numbers = $staffsGroupedByArea->flatten()->pluck('file_number');

        $devices = devices::all();
        $areas = area::all();
        $absenceReasons = absenceReason::all();
        $secretaries = secretary::all();
        $worker_status = ['Planta', 'Contratado'];

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
                    if ($absenceReason_id == 00) {
                        return $query->where('absenceReason_id', null);
                    } else {
                        return $query->whereIn('absenceReason_id', [$absenceReason_id]);
                    }
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
                if ($absenceReason_id == 00) {
                    return $query->where('absenceReason_id', null);
                } else {
                    return $query->whereIn('absenceReason_id', [$absenceReason_id]);
                }
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

        // Agrupar por área solo si tienen tardanzas
        $staffsGroupedByArea = $staffsGroupedByArea->filter(function ($staffs) use ($nonAttendances) {
            return $staffs->filter(function ($staff) use ($nonAttendances) {
                return $nonAttendances->contains('file_number', $staff->file_number);
            })->isNotEmpty();
        });

        return redirect()->route('reportView.nonAttendance')
            ->withInput()
            ->with([
                'nonAttendances' => $nonAttendances,
                'areas' => $areas->sortBy('name'),
                'absenceReasons' => $absenceReasons->sortBy('name'),
                'staffs' => $staffsGroupedByArea->flatten()->sortBy('name_surname'),
                'area_selected' => $area->name ?? 'Todas',
                'dates' => $date_range_checkbox ? 'Desde el ' . $date_from->format('d/m/y') . ' hasta el ' . $date_to->format('d/m/y') : (Carbon::parse($date)->format('d/m/y') == Carbon::now()->format('d/m/y') ? Carbon::now()->format('d/m/y H:i') : Carbon::parse($date)->format('d/m/y')),
                'secretaries' => $secretaries->sortBy('name'),
                'worker_status' => $worker_status,
                'staffsGrouped' => $staffsGroupedByArea,

            ]);
    }

    public function nonAttendanceByAreaExport(PDF $pdf, Request $request)
    {
        $data = $request->all();

        // Habilitar el soporte para procesamiento de PHP en DomPDF
        $pdf->set_option('isPhpEnabled', true);

        // Cargar la vista y generar el PDF
        $pdfInstance = $pdf->loadView('pdf.nonAttendanceByArea', $data);
        $fileName = preg_replace('/[\/\\\\:*?"<>|]/', '-', $request->file_name);

        return $pdfInstance->stream($fileName . '.pdf');
    }

    public function tardiesByAreaIndex(Request $request)
    {
        $areas = area::all();
        attendance_reports::truncate();
        $secretaries = secretary::all();
        $worker_status = ['Planta', 'Contratado'];

        return view('reports.tardiesByArea', [
            'areas' => $areas->sortBy('name'),
            'tardies' => session('tardies'),  // Pasar la variable desde la sesión si es necesario
            'staffs' => session('staffs') ? collect(session('staffs'))->sortBy('name_surname') : collect(), // Recuperar staffs
            'area_selected' => session('area_selected') ?? null,
            'tolerance' => session('tolerance') ?? null,
            'dates' =>  session('dates') ?? null,
            'secretaries' => $secretaries->sortBy('name'),
            'worker_status' => $worker_status,
            'staffsGrouped' => session('staffsGrouped') ?? null,


        ]);
    }

    public function tardiesByAreaSearch(Request $request)
    {
        $date_range_checkbox = $request->input('date_range_checkbox');
        $tolerance = $request->input('tolerance');
        $area_id = $request->input('area_id');
        $area = area::find($area_id);
        $secretary_id = $request->input('secretary_id') ?? null;
        $worker_status_input = ($request->input('worker_status') != "") ? strtolower($request->input('worker_status')) : null;

        $staffAreasQuery = staff_area::with('staff');
        if (!is_null($area_id)) {
            $staffAreasQuery->where('area_id', $area_id);
        }
        $staffAreas = $staffAreasQuery->get();

        $staffsGroupedByArea = $staffAreas->groupBy('area_id')->map(function ($group) use ($secretary_id, $worker_status_input) {
            return $group->pluck('staff')
                ->filter() // Elimina null
                ->filter(function ($staff) use ($secretary_id, $worker_status_input) {
                    return $staff->marking &&
                        (is_null($secretary_id) || $staff->secretary_id == $secretary_id) &&
                        (is_null($worker_status_input) || strtolower($staff->worker_status) == $worker_status_input);
                });
        })->filter(function ($staffs) {
            return $staffs->isNotEmpty(); // Oculta áreas vacías
        })->sortKeys()->sortBy(function ($staffs, $area_id) {
            return area::find($area_id)?->name ?? '';
        });

        $file_numbers = $staffsGroupedByArea->flatten()->pluck('file_number');

        $devices = devices::all();
        $areas = area::all();
        $secretaries = secretary::all();
        $worker_status = ['Planta', 'Contratado'];
        $devicesLogs = $this->getDeviceLogs($devices);

        foreach ($file_numbers as $file_number) {
            $this->processClockLogs($devicesLogs, $file_number);
        }

        if ($date_range_checkbox) {
            $date_from = Carbon::parse($request->input('date_from'));
            $date_to = Carbon::parse($request->input('date_to'));
            $counter = 1;
            $lastFileNumber = null;

            $clockLogs = clockLogs::whereDate('timestamp', '>=', $date_from)
                ->whereDate('timestamp', '<=', $date_to)->get();
            $this->updateAttendanceFromClockLogs($clockLogs);

            $tardies = attendance_reports::whereBetween('attendance_reports.date', [$date_from, $date_to])
                ->whereIn('attendance_reports.file_number', $file_numbers)
                ->whereIn('attendance_reports.id', function ($query) use ($date_from, $date_to) {
                    $query->selectRaw('MIN(id)')
                        ->from('attendance_reports as sub')
                        ->whereBetween('sub.date', [$date_from, $date_to])
                        ->groupBy('sub.date', 'sub.file_number');
                })
                ->join('staff', 'staff.file_number', '=', 'attendance_reports.file_number')
                ->join('schedule_staff', 'schedule_staff.staff_id', '=', 'staff.id')
                ->join('schedule', function ($join) {
                    $join->on('schedule.id', '=', 'schedule_staff.schedule_id')
                        ->whereRaw('WEEKDAY(attendance_reports.date) + 1 = schedule.day_id');
                })
                ->join('shifts', 'shifts.id', '=', 'schedule.shift_id')
                ->select(
                    'attendance_reports.*',
                    DB::raw('ANY_VALUE(shifts.startTime) as startTime'),
                    DB::raw('ANY_VALUE(shifts.endTime) as endTime')
                )
                ->groupBy('attendance_reports.id')
                ->orderBy('attendance_reports.file_number', 'ASC')
                ->orderBy('attendance_reports.date', 'ASC')
                ->get()
                ->filter(function ($item) use ($tolerance) {
                    $startTime = $item->startTime ?? null;
                    $entryTime = $item->entryTime ?? null;
                    if (!$startTime || !$entryTime) return false;

                    $allowedEntry = Carbon::parse($startTime)->addMinutes($tolerance);
                    return Carbon::parse($entryTime)->greaterThan($allowedEntry);
                })
                ->map(function ($item) use (&$counter, &$lastFileNumber) {
                    if ($item->file_number !== $lastFileNumber && $lastFileNumber !== null) {
                        $counter = 1;
                    }

                    $item->counter = $counter++;
                    $lastFileNumber = $item->file_number;

                    $item->date_formated = Carbon::parse($item->date)->format('d/m/y');
                    $item->day = ucfirst(Carbon::createFromFormat('d/m/y', $item->date_formated)->locale('es')->translatedFormat('l'));
                    $item->asssignedSchedule = $item->startTime . ' - ' . $item->endTime;

                    return $item;
                });
        } else {
            $counter = 1;
            $lastFileNumber = null;
            $date = $request->input('date');

            $clockLogs = clockLogs::whereDate('timestamp', '=', $date)->get();
            $this->updateAttendanceFromClockLogs($clockLogs);

            $tardies = attendance_reports::where('attendance_reports.date', $date)
                ->whereIn('attendance_reports.file_number', $file_numbers)
                ->whereIn('attendance_reports.id', function ($query) use ($date) {
                    $query->selectRaw('MIN(id)')
                        ->from('attendance_reports as sub')
                        ->where('sub.date', $date)
                        ->groupBy('sub.date', 'sub.file_number');
                })
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
                    if (!$startTime || !$entryTime) return false;

                    $allowedEntry = Carbon::parse($startTime)->addMinutes($tolerance);
                    return Carbon::parse($entryTime)->greaterThan($allowedEntry);
                })
                ->map(function ($item) use (&$counter, &$lastFileNumber) {
                    if ($item->file_number !== $lastFileNumber && $lastFileNumber !== null) {
                        $counter = 1;
                    }

                    $item->counter = $counter++;
                    $lastFileNumber = $item->file_number;

                    $item->date_formated = Carbon::parse($item->date)->format('d/m/y');
                    $item->day = ucfirst(Carbon::createFromFormat('d/m/y', $item->date_formated)->locale('es')->translatedFormat('l'));
                    $item->asssignedSchedule = $item->startTime . ' - ' . $item->endTime;

                    return $item;
                });
        }


        // Agrupar por área solo si tienen tardanzas
        $staffsGroupedByArea = $staffsGroupedByArea->filter(function ($staffs) use ($tardies) {
            return $staffs->filter(function ($staff) use ($tardies) {
                return $tardies->contains('file_number', $staff->file_number);
            })->isNotEmpty();
        });


        return redirect()->route('reportView.tardies')
            ->withInput()
            ->with([
                'tardies' => $tardies,
                'areas' => $areas->sortBy('name'),
                'staffs' => $staffsGroupedByArea->flatten()->sortBy('name_surname'),
                'area_selected' => $area->name ?? null,
                'tolerance' => $tolerance,
                'dates' => $date_range_checkbox
                    ? 'Desde el ' . $date_from->format('d/m/y') . ' hasta el ' . $date_to->format('d/m/y')
                    : Carbon::parse($date)->format('d/m/y'),
                'secretaries' => $secretaries->sortBy('name'),
                'worker_status' => $worker_status,
                'staffsGrouped' => $staffsGroupedByArea,
            ]);
    }



    public function tardiesByAreaExport(PDF $pdf, Request $request)
    {
        $data = $request->all();

        // Habilitar el soporte para procesamiento de PHP en DomPDF
        $pdf->set_option('isPhpEnabled', true);

        // Cargar la vista y generar el PDF
        $pdfInstance = $pdf->loadView('pdf.tardiesByArea', $data);

        $fileName = preg_replace('/[\/\\\\:*?"<>|]/', '-', $request->file_name);

        return $pdfInstance->stream($fileName . '.pdf');
    }

    public function attendanceSearch($id)
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

        if ($staff->collective_agreement) {
            $absenceReasons = absenceReason::where('decree', $staff->collective_agreement->name)->where('logical_erase', false)->get();
        } else {
            $absenceReasons = collect(); // Devuelve una colección vacía si no hay convenio colectivo
        }


        // Obtener mes y año actuales por si no están presentes en la solicitud
        $month = now()->month; // Mes actual si no se proporciona
        $year = now()->year; // Año actual si no se proporciona

        // Filtrar los registros de asistencia según el mes y el año
        $attendance = attendance::where('file_number', $file_number)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get()
            ->map(function ($item) {
                // Formatear las fechas en formato dd/mm/yy

                $item->date_formated = Carbon::parse($item->date)->format('d/m/y');
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
        $nonAttendanceRaw = NonAttendance::where('file_number', $file_number)->whereYear('date', $year)
            ->with('absenceReason')
            ->get(); // SIN filtrar por mes ni año

        $nonAttendance = $nonAttendanceRaw->filter(function ($item) use ($month, $year) {
            return Carbon::parse($item->date)->month == $month &&
                Carbon::parse($item->date)->year == $year;
        })->map(function ($item) use ($schedules, $attendanceDates) {
            if (in_array($item->date, $attendanceDates)) {
                NonAttendance::where('id', $item->id)->delete();
                return null;
            }

            $item->date = Carbon::parse($item->date)->format('d/m/y');
            $item->day = Carbon::createFromFormat('d/m/y', $item->date)->locale('es')->translatedFormat('l');
            $item->day = ucfirst($item->day);

            // NO sobreescribas el objeto
            $item->absence_reason_name = $item->absenceReason->name ?? null;

            return $item;
        })->filter();

        $dataToExport = [
            'staff' => $staff->toArray(),
            'days' => $days,
            'totalHours' => $totalHoursFormatted,
            'hoursAverage' => $hoursAverageFormatted,
            'totalExtraHours' => $totalExtraHoursFormatted,
            'schedules' => $schedules->toArray(),
            'attendances' => $attendance->sortBy('date')->values()->toArray(),
            'non_attendances' => $nonAttendance->sortBy('date')->values()->toArray(),
        ];

        return response()->json($dataToExport);
    }

    public function attendanceExport(Request $request)
    {
        // Decodificar datos
        $staff = json_decode($request->input('staff'), true);
        $days = json_decode($request->input('days'), true);
        $totalHours = json_decode($request->input('totalHours'), true);
        $hoursAverage = json_decode($request->input('hoursAverage'), true);
        $totalExtraHours = json_decode($request->input('totalExtraHours'), true);
        $schedules = json_decode($request->input('schedules'), true);
        $attendances = json_decode($request->input('attendances'), true);
        $non_attendances = json_decode($request->input('non_attendances'), true);
        $row = 1;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte');


        // --- RESUMEN ---
        $sheet->setCellValue("A$row", "Universidad Nacional de Cuyo - Hospital universitario");
        $sheet->mergeCells("A$row:C$row");
        $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(14);

        $row += 2;
        Log::info('STAFF:', [$staff]);
        Log::info('Tipo de STAFF:', [gettype($staff)]);

        $infoPersonal = [
            'Legajo' => $staff['file_number'] ?? 'Sin legajo',
            'Nombre y apellido' => $staff['name_surname'] ?? 'Sin nombre',
            'Cordinador' => ($coordinator = coordinator::find($staff['coordinator_id'] ?? null))
                ? (($coordinatorStaff = staff::find($coordinator->staff_id)) ? $coordinatorStaff->name_surname : 'Sin coordinador asignado')
                : 'Sin coordinador asignado',
            'Condición' => ($staff['worker_status'] == 'planta')
                ? 'Planta - ' . (($category = category::find($staff['category_id'] ?? null)) ? $category->name : 'Sin categoría asignada')
                : 'Contratado',
            'Secretaría' => ($secretary = secretary::find($staff['secretary_id'] ?? null))
                ? $secretary->name
                : 'Sin secretaría asignada',
            'Fecha de ingreso' => $staff['date_of_entry']
                ? Carbon::parse($staff['date_of_entry'])->format('d/m/y')
                : 'Sin fecha',
            'Fecha de baja' => $staff['inactive_since']
                ? Carbon::parse($staff['inactive_since'])->format('d/m/y')
                : '—',
            'Fecha de reporte' => Carbon::now()->format('d/m/y'),
            'Marca' => ($staff['marking'] ?? false) ? '✔' : '✘',
        ];


        $sheet->setCellValue("A$row", "Información personal:");
        $sheet->mergeCells("A$row:B$row");
        $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(12);
        $row++;

        // Agregar los datos en vertical
        foreach ($infoPersonal as $titulo => $valor) {
            $sheet->setCellValue("A$row", $titulo);
            $sheet->setCellValueExplicit("B$row", $valor, DataType::TYPE_STRING); // Siempre como texto por seguridad
            $sheet->getStyle("A$row")->getFont()->setBold(true);
            $row++;
        }

        $row++;

        $sheet->setCellValue("A$row", "Resumen:");
        $sheet->mergeCells("A$row:G$row");
        $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(12);
        $row++;
        $resumen = [
            ['Días completados', $days . ' '],
            ['Horas totales', $totalHours],
            ['Promedio de horas', $hoursAverage],
            ['Horas adicionales', $totalExtraHours],
        ];

        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $horarios = [];
        $horas = [];

        foreach (range(1, 7) as $i) {
            $schedule = collect($schedules)->firstWhere('day_id', $i);

            if ($schedule) {
                $shift = Shift::find($schedule['shift_id']);
                $start = Carbon::parse($shift->startTime)->format('H:i');
                $end = Carbon::parse($shift->endTime)->format('H:i');
                $horarios[] = "$start - $end";
                $horas[] = Carbon::parse($shift->startTime)->diffInHours($shift->endTime) . ' horas';
            } else {
                $horarios[] = "Sin horario";
                $horas[] = 0 . ' horas';
            }
        }

        $startCol = 'A';
        $col = $startCol;

        // --- Fila 1: títulos resumen ---
        foreach ($resumen as $item) {
            $sheet->setCellValue("$col$row", $item[0]);
            $sheet->getStyle("$col$row")->getFont()->setBold(true);
            $col++;
        }
        $row++;

        // --- Fila 2: valores resumen ---
        $col = $startCol;
        foreach ($resumen as $item) {
            $sheet->setCellValue("$col$row", $item[1]);
            $col++;
        }
        $row++;

        // --- Fila 3: días semana ---
        $col = $startCol;
        foreach ($diasSemana as $dia) {
            $sheet->setCellValue("$col$row", $dia);
            $sheet->getStyle("$col$row")->getFont()->setBold(true);
            $col++;
        }
        $row++;

        // --- Fila 4: rangos horarios ---
        $col = $startCol;
        foreach ($horarios as $horario) {
            $sheet->setCellValue("$col$row", $horario);
            $col++;
        }
        $row++;

        // --- Fila 5: horas por día ---
        $col = $startCol;
        foreach ($horas as $hora) {
            $sheet->setCellValue("$col$row", $hora);
            $col++;
        }

        $endCol = chr(ord($startCol) + max(count($resumen), count($diasSemana)) - 1);
        $endRow = $row;

        $row += 2;

        // --- DETALLE DE ASISTENCIAS ---
        $sheet->setCellValue("A$row", "Detalle de Asistencias:");
        $sheet->mergeCells("A$row:G$row");
        $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(12);
        $row++;

        $headers = ['Día', 'Fecha', 'Entrada', 'Salida', 'Horas cumplidas', 'Horas adicionales', 'Observaciones'];
        $sheet->fromArray($headers, null, "A$row");
        $sheet->getStyle("A$row:G$row")->getFont()->setBold(true);
        $row++;

        foreach ($attendances as $registro) {
            $sheet->fromArray([
                $registro['day'],
                $registro['date_formated'],
                $registro['entryTime'],
                $registro['departureTime'],
                $registro['hoursCompleted'],
                $registro['extraHours'],
                $registro['observations'],
            ], null, "A$row");
            $row++;
        }

        $row++;

        $sheet->getColumnDimension('C')->setWidth(40); // Motivo/justificación
        $sheet->getColumnDimension('D')->setWidth(30); // Observaciones


        // --- DETALLE DE ASISTENCIAS ---
        if (count($non_attendances) > 0) {
            $sheet->setCellValue("A$row", "Detalle de Inasistencias:");
            $sheet->mergeCells("A$row:G$row");
            $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(12);
            $row++;

            $headers = ['Día', 'Fecha', 'Motivo/justificación', 'Observaciones'];
            $sheet->fromArray($headers, null, "A$row");
            $sheet->getStyle("A$row:G$row")->getFont()->setBold(true);
            $row++;

            foreach ($non_attendances as $registro) {
                $sheet->fromArray([
                    $registro['day'],
                    $registro['date'],
                    ($registro['absenceReason_id']) ? absenceReason::find($registro['absenceReason_id'])->name : '-',
                    $registro['observations'],
                ], null, "A$row");

                // Aplicar wrap text SOLO a C y D en esa fila
                $sheet->getStyle("C$row:D$row")->getAlignment()->setWrapText(true);

                $row++;
            }
        }

        // Estilo rápido para toda la hoja
        $sheet->getStyle("A1:G$row")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth(20); // Ajuste general
        }


        $sheet->getPageSetup()->setPrintArea("A1:G$row");
        $sheet->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0); // 0 = no limitar filas
        $pageMargins = $sheet->getPageMargins();
        $pageMargins->setTop(0.1);
        $pageMargins->setBottom(0.1);
        $pageMargins->setLeft(0.1);
        $pageMargins->setRight(0.1);
        $sheet->getPageSetup()->setScale(75); // podés ajustar el porcentaje

        // Descargar el archivo
        $writer = new Xlsx($spreadsheet);
        $filename = $request->input('file_name') . '.xlsx';
        Log::info('AAAAAAAAAAA');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function nonAttendanceByAreaExportExcel(Request $request)
    {
        $nonAttendances = json_decode($request->nonAttendances, true);
        $staffs = json_decode($request->staffs, true);
        $areas = collect(json_decode($request->areas, true)); // Asegurate de enviar esto desde la vista
        $dates = $request->dates;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Título y subtítulo
        $row = 1;
        $sheet->setCellValue("A{$row}", "Universidad Nacional de Cuyo - Hospital Universitario");
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue("A{$row}", "Reporte de ausentismo");
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row += 2;
        $sheet->setCellValue("A{$row}", "Fecha:");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->setCellValue("B{$row}", $dates);

        $row += 2;

        // Agrupar staffs por área
        $groupedStaffs = [];

        foreach ($staffs as $staff) {
            $areaId = optional(Staff::find($staff['id'])->areas()->first())->id;
            $groupedStaffs[$areaId][] = $staff;
        }

        foreach ($groupedStaffs as $areaId => $staffGroup) {
            $areaStartRow = $row; // Marcar inicio del área

            $areaName = optional($areas->firstWhere('id', $areaId))['name'] ?? 'Área desconocida';

            // Título del área
            $sheet->setCellValue("A{$row}", $areaName);
            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $row++;

            foreach ($staffGroup as $staff) {
                $staffAbsences = array_filter($nonAttendances, fn($a) => $a['file_number'] === $staff['file_number']);
                if (count($staffAbsences) === 0) continue;

                // Título del agente
                $sheet->setCellValue("A{$row}", "#{$staff['file_number']} {$staff['name_surname']} - " . count($staffAbsences) . ' Inasistencia' . (count($staffAbsences) > 1 ? 's' : ''));
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;

                // Encabezados
                $headers = ['#', 'Día', 'Fecha', 'Motivo/Justificación', 'Observaciones'];
                foreach ($headers as $i => $header) {
                    $col = chr(65 + $i); // A, B, C, D, E
                    $sheet->setCellValue("{$col}{$row}", $header);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
                }
                $row++;

                // Detalle de inasistencias
                foreach ($staffAbsences as $absence) {
                    $sheet->setCellValue("A{$row}", $absence['counter']);
                    $sheet->setCellValue("B{$row}", $absence['day']);
                    $sheet->setCellValue("C{$row}", $absence['date_formated']);
                    $sheet->setCellValue("D{$row}", $absence['absenceReason'] ?? '-');
                    $sheet->setCellValue("E{$row}", $absence['observations'] ?? '-');
                    $row++;
                }

                $row++; // espacio después del agente
            }

            $areaEndRow = $row - 1; // Fin del área

            // Aplicar borde grueso alrededor del bloque del área
            $sheet->getStyle("A{$areaStartRow}:E{$areaEndRow}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

            $row++; // espacio después del área
        }

        // Configuración de página
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPrintArea("A1:E{$row}");
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setScale(75);
        $pageMargins = $sheet->getPageMargins();
        $pageMargins->setTop(0.1)->setBottom(0.1)->setLeft(0.1)->setRight(0.1);

        // Guardado y respuesta
        $writer = new Xlsx($spreadsheet);

        // Limpiar el nombre del archivo
        $rawFileName = $request->input('file_name');
        $safeFileName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $rawFileName);
        $filename = $safeFileName . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function tardinessReportExportExcel(Request $request)
    {
        $tardies = json_decode($request->tardies, true);
        $staffs = json_decode($request->staffs, true);
        $areas = collect(json_decode($request->areas, true));
        $dates = $request->dates;
        $tolerance = $request->tolerance;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;

        // Encabezado general
        $sheet->setCellValue("A{$row}", "Universidad Nacional de Cuyo - Hospital Universitario");
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue("A{$row}", "Reporte de tardanzas");
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $row++;
        $sheet->setCellValue("A{$row}", "Fecha:");
        $sheet->setCellValue("B{$row}", $dates);
        $row++;

        $sheet->setCellValue("A{$row}", "Tolerancia:");
        $sheet->setCellValue("B{$row}", "{$tolerance} minutos");
        $row += 2;

        // Agrupar por área
        $groupedStaffs = [];
        foreach ($staffs as $staff) {
            $areaId = optional(Staff::find($staff['id'])->areas()->first())->id;
            $groupedStaffs[$areaId][] = $staff;
        }

        foreach ($groupedStaffs as $areaId => $staffGroup) {
            $areaName = optional($areas->firstWhere('id', $areaId))['name'] ?? 'Área desconocida';

            $areaStartRow = $row;

            // Título del área
            $sheet->setCellValue("A{$row}", $areaName);
            $sheet->mergeCells("A{$row}:G{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $row++;

            foreach ($staffGroup as $staff) {
                $staffTardies = array_filter($tardies, fn($t) => $t['file_number'] === $staff['file_number']);
                if (count($staffTardies) === 0) continue;

                // Encabezado del agente
                $sheet->setCellValue("A{$row}", "#{$staff['file_number']} {$staff['name_surname']} - " . count($staffTardies) . ' Tardanza' . (count($staffTardies) > 1 ? 's' : ''));
                $sheet->mergeCells("A{$row}:G{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;

                // Encabezados de tabla
                $headers = ['#', 'Día', 'Fecha', 'Horario', 'Entrada', 'Salida', 'Horas cumplidas'];
                foreach ($headers as $i => $header) {
                    $col = chr(65 + $i); // A-G
                    $sheet->setCellValue("{$col}{$row}", $header);
                    $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                $row++;

                // Tardanzas
                foreach ($staffTardies as $tardy) {
                    $sheet->setCellValue("A{$row}", $tardy['counter']);
                    $sheet->setCellValue("B{$row}", $tardy['day']);
                    $sheet->setCellValue("C{$row}", $tardy['date_formated']);
                    $sheet->setCellValue("D{$row}", $tardy['asssignedSchedule'] ?? '-');
                    $sheet->setCellValue("E{$row}", $tardy['entryTime'] ?? '-');
                    $sheet->setCellValue("F{$row}", $tardy['departureTime'] ?? '-');
                    $sheet->setCellValue("G{$row}", $tardy['hoursCompleted'] ?? '-');
                    $row++;
                }

                $row++; // Espacio después del staff
            }

            $areaEndRow = $row - 1;
            // Aplicar bordes al área completa
            $sheet->getStyle("A{$areaStartRow}:G{$areaEndRow}")->applyFromArray([
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);

            $row++; // Espacio después del área
        }

        // Configuración de página
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setPrintArea("A1:G{$row}");
        $sheet->getPageSetup()->setScale(80);
        $sheet->getPageMargins()->setTop(0.3)->setBottom(0.3)->setLeft(0.3)->setRight(0.3);

        // Exportar
        $writer = new Xlsx($spreadsheet);
        $safeFileName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $request->input('file_name', 'reporte_tardanzas'));
        $filename = $safeFileName . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function nonAttendanceByAreaExpIndex(Request $request)
    {
        $areas = area::all();
        $absenceReasons = absenceReason::all();
        $secretaries = secretary::all();
        $worker_status = ['Planta', 'Contratado'];
        nonAttendance_reports::truncate();

        return view('reports.nonAttendanceExpByArea', [
            'absenceReasons' => $absenceReasons->sortBy('name'),
            'areas' => $areas->sortBy('name'),
            'nonAttendances' => session('nonAttendances'),  // Pasar la variable desde la sesión si es necesario
            'staffs' => session('staffs') ? collect(session('staffs'))->sortBy('name_surname') : collect(), // Recuperar staffs
            'area_selected' => session('area_selected') ?? null,
            'absenceReason_selected' => session('absenceReason_selected') ?? null,
            'dates' =>  session('dates') ?? null,
            'secretaries' => $secretaries->sortBy('name'),
            'worker_status' => $worker_status,
            'staffsGrouped' => session('staffsGrouped') ?? null,

        ]);
    }

    public function nonAttendanceByAreaSearchExp(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $area_id = $request->input('area_id');
        $absenceReason_id = $request->input('absenceReason_id');
        $secretary_id = $request->input('secretary_id');
        $area = area::find($area_id);
        $worker_status_input = ($request->input('worker_status') != "") ? strtolower($request->input('worker_status')) : null;

        $staffAreasQuery = staff_area::with('staff');
        if (!is_null($area_id)) {
            $staffAreasQuery->where('area_id', $area_id);
        }
        $staffAreas = $staffAreasQuery->get();

        $staffsGroupedByArea = $staffAreas->groupBy('area_id')->map(function ($group) use ($secretary_id, $worker_status_input) {
            return $group->pluck('staff')
                ->filter()
                ->filter(function ($staff) use ($secretary_id, $worker_status_input) {
                    return $staff->marking &&
                        (is_null($secretary_id) || $staff->secretary_id == $secretary_id) &&
                        (is_null($worker_status_input) || strtolower($staff->worker_status) == $worker_status_input);
                });
        })->filter(function ($staffs) {
            return $staffs->isNotEmpty();
        })->sortKeys()->sortBy(function ($staffs, $area_id) {
            return area::find($area_id)?->name ?? '';
        });

        $file_numbers = $staffsGroupedByArea->flatten()->pluck('file_number');

        $devices = devices::all();
        $areas = area::all();
        $absenceReasons = absenceReason::all();
        $secretaries = secretary::all();
        $worker_status = ['Planta', 'Contratado'];

        $devicesLogs = $this->getDeviceLogs($devices);

        foreach ($file_numbers as $file_number) {
            $this->processClockLogs($devicesLogs, $file_number);
        }

        // Nuevo: Filtrado por mes y año
        $date_from = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $date_to = $date_from->copy()->endOfMonth();

        $clockLogs = clockLogs::whereDate('timestamp', '>=', $date_from)
            ->whereDate('timestamp', '<=', $date_to)
            ->get();

        $this->updateAttendanceFromClockLogs($clockLogs);

        foreach ($file_numbers as $file_number) {
            $this->createNonAttendance($file_number, $date_from, $date_to, []);
        }

        $nonAttendances = NonAttendance_reports::whereMonth('date', $month)
            ->whereIn('file_number', $file_numbers)
            ->whereNotNull('absenceReason_id') // Solo justificadas
            ->when(!is_null($absenceReason_id), function ($query) use ($absenceReason_id) {
                return $query->where('absenceReason_id', $absenceReason_id);
            })
            ->with(['staff', 'absenceReason'])
            ->orderBy('absenceReason_id')
            ->orderBy('file_number')
            ->orderBy('date')
            ->get();

        // Agrupar como ya lo tenías
        $groupedByReason = $nonAttendances
            ->sortBy([['absenceReason.name', 'asc'], ['staff.name_surname', 'asc'], ['date', 'asc']])
            ->groupBy('absenceReason_id')
            ->map(function ($group, $reason_id) {
                $reasonName = optional($group->first()->absenceReason)->name ?? 'Sin motivo';
                $reasonDecree = optional($group->first()->absenceReason)->decree ?? '';
                $reasonArticle = optional($group->first()->absenceReason)->article ?? '';
                $reasonSubsection = optional($group->first()->absenceReason)->subsection ?? '';

                $groupedByStaff = $group->groupBy('file_number')->map(function ($staffGroup) {
                    $staffGroup = $staffGroup->sortBy('date')->values();
                    $result = collect();
                    $start = null;
                    $end = null;
                    $count = 0;

                    foreach ($staffGroup as $i => $item) {
                        $currentDate = Carbon::parse($item->date);

                        if (is_null($start)) {
                            $start = $currentDate;
                            $end = $currentDate;
                            $count = 1;
                            continue;
                        }

                        $previousDate = Carbon::parse($staffGroup[$i - 1]->date);

                        if ($currentDate->diffInDays($previousDate) == 1) {
                            $end = $currentDate;
                            $count++;
                        } else {
                            $result->push([
                                'date_range' => $start->equalTo($end)
                                    ? $start->format('d/m/Y')
                                    : $start->format('d/m/Y') . ' al ' . $end->format('d/m/Y'),
                                'days_count' => $count,
                            ]);

                            $start = $currentDate;
                            $end = $currentDate;
                            $count = 1;
                        }
                    }

                    if (!is_null($start)) {
                        $result->push([
                            'date_range' => $start->equalTo($end)
                                ? $start->format('d/m/Y')
                                : $start->format('d/m/Y') . ' al ' . $end->format('d/m/Y'),
                            'days_count' => $count,
                        ]);
                    }

                    $staff = $staffGroup->first()->staff;

                    return [
                        'file_number' => $staff->file_number,
                        'name' => $staff->name_surname,
                        'rango_fechas' => $result,
                    ];
                });

                $reason_data = [
                    'article' => $reasonArticle,
                    'subsection' => $reasonSubsection,
                    'decree' => $reasonDecree,
                ];

                return [
                    'reason' => $reasonName,
                    'reason_data' => $reason_data,
                    'staffs' => $groupedByStaff->sortBy('file_number')->values(),
                ];
            })->values();


        return redirect()->route('reportView.nonAttendanceExp')
            ->withInput()
            ->with([
                'nonAttendances' => $groupedByReason,
                'areas' => $areas->sortBy('name'),
                'absenceReasons' => $absenceReasons->sortBy('name'),
                'staffs' => $staffsGroupedByArea->flatten()->sortBy('name_surname'),
                'area_selected' => $area->name ?? 'Todas',
                'dates' => 'Mes de ' . ucfirst(Carbon::create()->month($month)->monthName) . ' del ' . $year,
                'secretaries' => $secretaries->sortBy('name'),
                'worker_status' => $worker_status,
                'staffsGrouped' => $staffsGroupedByArea,
            ]);
    }

    public function nonAttendanceByAreaExcelExp(Request $request)
    {
        $nonAttendances = collect(json_decode($request->nonAttendances, true));
        $staffs = collect(json_decode($request->staffs, true));
        $areas = collect(json_decode($request->areas, true));
        $area_selected = $request->area_selected;
        $dates = $request->dates;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;

        // Encabezado principal
        $sheet->setCellValue("A{$row}", "Universidad Nacional de Cuyo - Hospital Universitario");
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue("A{$row}", "LICENCIAS Y JUSTIFICACION DE INASISTENCIAS");
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue("A{$row}", "Área:");
        $sheet->setCellValue("B{$row}", $area_selected);
        $row++;

        $sheet->setCellValue("A{$row}", "Fechas:");
        $sheet->setCellValue("B{$row}", $dates);
        $row += 2;

        foreach ($nonAttendances as $group) {
            $sheet->setCellValue("A{$row}", $group['reason']);
            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $row++;

            $info = $group['reason_data'] ?? null;
            if ($info) {
                $descripcion = [];
                if ($info['article']) $descripcion[] = "Artículo: {$info['article']}";
                if ($info['subsection']) $descripcion[] = "Inciso: {$info['subsection']}";
                if ($info['decree']) $descripcion[] = "Convenio: {$info['decree']}";
                $sheet->setCellValue("A{$row}", implode(' - ', $descripcion));
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(10);
                $row++;
            }

            // Encabezado tabla
            $headers = ['#Legajo', 'Nombre', 'Días', 'Fecha(s)'];
            foreach ($headers as $i => $header) {
                $col = chr(65 + $i); // A-D
                $sheet->setCellValue("{$col}{$row}", $header);
                $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            $row++;

            $datos = collect($group['staffs'])->flatMap(function ($staff) {
                return collect($staff['rango_fechas'])->map(function ($rango) use ($staff) {
                    return [
                        'file_number' => '#' . $staff['file_number'],
                        'name' => $staff['name'],
                        'date_range' => $rango['date_range'],
                        'days_count' => $rango['days_count'],
                    ];
                });
            });

            foreach ($datos as $registro) {
                $sheet->setCellValue("A{$row}", $registro['file_number']);
                $sheet->setCellValue("B{$row}", $registro['name']);
                $sheet->setCellValue("C{$row}", $registro['days_count']);
                $sheet->setCellValue("D{$row}", $registro['date_range']);
                $row++;
            }

            $row++; // espacio entre grupos
        }

        // Configuración de página
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.3)->setBottom(0.3)->setLeft(0.3)->setRight(0.3);

        // Descargar archivo
        $writer = new Xlsx($spreadsheet);
        $safeFileName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $request->input('file_name', 'reporte_inasistencias'));
        $filename = $safeFileName . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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

    public function createNonAttendance($file_number, $date_from = null, $date_to = null, $specific_dates = [])
    {
        $clockLogsController = new clockLogsController();
        $staff = Staff::where('file_number', $file_number)->first();

        $bulkInsert = [];
        $addedDates = [];

        // Clonar inasistencias ya registradas a la tabla temporal
        $existingReports = NonAttendance_reports::where('file_number', $file_number)
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $existingNonAttendancesQuery = NonAttendance::where('file_number', $file_number);

        if (!empty($specific_dates)) {
            $specific_dates = collect($specific_dates)
                ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
                ->unique()
                ->toArray();

            $existingNonAttendancesQuery->whereIn('date', $specific_dates);
        } elseif ($date_from && $date_to) {
            $existingNonAttendancesQuery->whereBetween('date', [$date_from, $date_to]);
        }

        $existingNonAttendances = $existingNonAttendancesQuery->get();

        $manualInsert = [];

        foreach ($existingNonAttendances as $na) {
            $formattedDate = Carbon::parse($na->date)->format('Y-m-d');

            if (!in_array($formattedDate, $existingReports)) {
                $manualInsert[] = [
                    'file_number' => $na->file_number,
                    'date' => $formattedDate,
                    'absenceReason_id' => $na->absenceReason_id,
                    'observations' => $na->observations,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $addedDates[] = $formattedDate;
            }
        }

        if (!empty($manualInsert)) {
            NonAttendance_reports::insert($manualInsert);
        }

        if (!empty($specific_dates)) {
            $attendances = clockLogs::where('file_number', $file_number)
                ->whereIn(DB::raw('DATE(timestamp)'), $specific_dates)
                ->pluck('timestamp')
                ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
                ->toArray();

            $nonAttendances = NonAttendance::where('file_number', $staff->file_number)
                ->whereIn('date', $specific_dates)
                ->get()
                ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

            // Validar que las fechas específicas sean días laborales para ese empleado
            $validWorkingDays = collect();

            foreach ($specific_dates as $date) {
                $carbonDate = Carbon::parse($date);
                $month = $carbonDate->month;
                $year = $carbonDate->year;

                $workingDays = $clockLogsController->getWorkingDays($staff->id, $month, $year);
                $formattedWorkingDays = collect($workingDays)
                    ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));

                if ($formattedWorkingDays->contains($date)) {
                    $validWorkingDays->push($date);
                }
            }

            foreach ($validWorkingDays as $date) {
                if (in_array($date, $attendances)) {
                    continue;
                }

                $actualDate = Carbon::now()->format('Y-m-d');

                if ($actualDate >= $date && !in_array($date, $addedDates)) {
                    $bulkInsert[] = [
                        'file_number' => $staff->file_number,
                        'date' => $date,
                        'absenceReason_id' => $nonAttendances[$date]->absenceReason_id ?? null,
                        'observations' => $nonAttendances[$date]->observations ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $addedDates[] = $date;
                }
            }
        } elseif ($date_from && $date_to) {
            $attendances = clockLogs::where('file_number', $file_number)
                ->whereBetween(DB::raw('DATE(timestamp)'), [$date_from, $date_to])
                ->pluck('timestamp')
                ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
                ->toArray();

            $years = range(Carbon::parse($date_from)->year, Carbon::parse($date_to)->year);
            $months = range(Carbon::parse($date_from)->month, Carbon::parse($date_to)->month);

            $nonAttendances = NonAttendance::where('file_number', $staff->file_number)
                ->get()
                ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

            foreach ($years as $year) {
                foreach ($months as $month) {
                    $workingDays = collect($clockLogsController->getWorkingDays($staff->id, $month, $year))
                        ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));

                    foreach ($workingDays as $workingDayFormatted) {
                        if (in_array($workingDayFormatted, $attendances)) {
                            continue;
                        }

                        $actualDate = Carbon::now()->format('Y-m-d');

                        if ($actualDate >= $workingDayFormatted && !in_array($workingDayFormatted, $addedDates)) {
                            $bulkInsert[] = [
                                'file_number' => $staff->file_number,
                                'date' => $workingDayFormatted,
                                'absenceReason_id' => $nonAttendances[$workingDayFormatted]->absenceReason_id ?? null,
                                'observations' => $nonAttendances[$workingDayFormatted]->observations ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $addedDates[] = $workingDayFormatted;
                        }
                    }
                }
            }
        }

        if (!empty($bulkInsert)) {
            NonAttendance_reports::insert($bulkInsert);
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
}
