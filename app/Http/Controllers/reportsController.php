<?php

namespace App\Http\Controllers;

use App\Models\area;
use App\Models\clockLogs;
use App\Models\devices;
use App\Models\NonAttendance;
use App\Models\staff_area;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Rats\Zkteco\Lib\ZKTeco;

class reportsController extends Controller
{
    public function nonAttendanceIndex()
    {
        $areas = area::all();

        return view('reports.nonAttendance', [
            'areas' => $areas
        ]);
    }

    public function nonAttendanceSearch(Request $request)
    {
        $clockLogsController = new clockLogsController();
        $date_range_checkbox = $request->input('date_range_checkbox');
        $area_id = $request->input('area_id');
        $staffs = staff_area::where('area_id', $area_id)->with('staff')->get()->pluck('staff');
        $file_numbers = $staffs->pluck('file_number');
        $devices = devices::all();
        $areas = area::all();


        // $devicesLogs = $this->getDeviceLogs($devices);

        // foreach ($file_numbers as $file_number) {
        //     $this->processClockLogs($devicesLogs, $file_number);
        //     $clockLogsController->updateAttendanceFromClockLogs($file_number); //Crea las asistencias y las inasistencias
        // }
        if ($date_range_checkbox) {
            $date_from = Carbon::parse($request->input('date_from'));
            $date_to = Carbon::parse($request->input('date_to'));

            $clockLogs = clockLogs::whereDate('timestamp', '>=', $date_from)->whereDate('timestamp', '<=', $date_to)->get();

            $nonAttendances = NonAttendance::where('date', '>=', $date_from)->where('date', '<=', $date_to)->whereIn('file_number', $file_numbers)->with('staff')->get()->map(function ($item) {
                // Formatear las fechas en formato dd/mm/yy
                $item->date = Carbon::parse($item->date)->format('d/m/y');
                $item->day = Carbon::createFromFormat('d/m/y', $item->date)->locale('es')->translatedFormat('l');
                $item->day = ucfirst($item->day);
                $item->absenceReason = $item->absenceReason->name ?? null;

                return $item;
            });
        } else {
            $date = $request->input('date');

            $clockLogs = clockLogs::whereDate('timestamp', '>=', $date)->get();

            $nonAttendances = NonAttendance::where('date', $date)->whereIn('file_number', $file_numbers)->get()->map(function ($item) {
                // Formatear las fechas en formato dd/mm/yy
                $item->date = Carbon::parse($item->date)->format('d/m/y');
                $item->day = Carbon::createFromFormat('d/m/y', $item->date)->locale('es')->translatedFormat('l');
                $item->day = ucfirst($item->day);
                $item->absenceReason = $item->absenceReason->name ?? null;

                return $item;
            });
        }


        return view('reports.nonAttendance', [
            'nonAttendances' => $nonAttendances,
            'areas' => $areas,
            'staffs' => $staffs->sortBy('name_surname')
        ]);
    }

    private function getDeviceLogs($devices)
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

    private function processClockLogs(array $logs, $file_number = null)
    {
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
                    'device_id' => $log['device_id']
                ]);
            }
        }
    }
}
