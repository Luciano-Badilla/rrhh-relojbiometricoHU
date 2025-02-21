<?php

namespace App\Http\Controllers;

use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Http\Request;
use App\Models\clockLogs;
use App\Models\attendance;
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
        } catch (\Exception $e) {
            Log::error("Error al actualizar la asistencia: {$e->getMessage()}");
            throw new \Exception("Hubo un problema al procesar los registros de asistencia.");
        }
    }


    private function createOrUpdateAttendance($entryLog, $exitLog = null)
    {
        // Obtener la fecha del registro
        $date = date('Y-m-d', strtotime($entryLog->timestamp));

        // Definir los tiempos de entrada y salida
        $entryTime = date('H:i:s', strtotime($entryLog->timestamp));
        $departureTime = $exitLog ? date('H:i:s', strtotime($exitLog->timestamp)) : null;

        // Buscar un registro existente para el mismo día y usuario
        $attendance = attendance::where('file_number', $entryLog->file_number)
            ->where('date', $date)
            ->where(function ($query) use ($entryTime, $departureTime) {
                // Buscar un registro sin salida o con la misma entrada y salida
                $query->whereNull('departureTime')
                    ->orWhere('entryTime', $entryTime)
                    ->orWhere('departureTime', $departureTime);
            })
            ->first();

        if ($attendance) {

            // Si existe un registro, actualizar los tiempos si es necesario
            if ($attendance->departureTime === $attendance->entryTime) {
                $attendance->update([
                    'entryTime' => min($attendance->entryTime, $entryTime),
                    'departureTime' => $departureTime ?? $attendance->departureTime,
                ]);
            }
        } else {
            // Crear un nuevo registro si no existe uno adecuado
            attendance::create([
                'file_number' => $entryLog->file_number,
                'date' => $date,
                'entryTime' => $entryTime,
                'departureTime' => $departureTime,
                'absenceReason_id' => null,
                'observations' => null,
            ]);
        }
    }
}
