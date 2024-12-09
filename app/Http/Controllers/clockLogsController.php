<?php

namespace App\Http\Controllers;

use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Http\Request;
use App\Models\clockLogs;
use App\Models\attendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class clockLogsController extends Controller
{
    public function update()
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
                    // Obtener todos los registros de asistencia
                    $logs = $zk->getAttendance();

                    // Agrupar logs por UID
                    $userLogs = [];

                    foreach ($logs as $log) {
                        // Verificar si el log ya existe en clockLogs, solo procesamos los nuevos logs
                        $exists = clockLogs::where('id', $log['uid'])->exists();

                        if (!$exists) {
                            // Guardar el log en clockLogs
                            clockLogs::create([
                                'id' => $log['uid'],
                                'file_number' => $log['id'],
                                'timestamp' => $log['timestamp'],
                                'device_id' => $device['device_id']
                            ]);
                        }

                        // Agrupar los registros por UID (usuario)
                        if (!isset($userLogs[$log['uid']])) {
                            $userLogs[$log['uid']] = [];
                        }

                        // Agregar el log al usuario correspondiente
                        $userLogs[$log['uid']][] = $log;
                    }

                    // Procesar los logs agrupados para insertar o actualizar en la tabla attendance
                    foreach ($userLogs as $uid => $userLogsArr) {
                        // Ordenar los logs por timestamp (en caso de que lleguen desordenados)
                        usort($userLogsArr, function ($a, $b) {
                            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
                        });

                        // Si hay solo un registro, se crea con salida null
                        if (count($userLogsArr) === 1) {
                            $entryLog = $userLogsArr[0];
                            $existingAttendance = attendance::where('file_number', $entryLog['id'])
                                ->where('date', date('Y-m-d', strtotime($entryLog['timestamp'])))
                                ->where('entryTime', date('H:i:s', strtotime($entryLog['timestamp'])))
                                ->first();

                            if (!$existingAttendance) {
                                // Crear el nuevo registro de asistencia si no existe
                                attendance::create([
                                    'file_number' => $entryLog['id'],
                                    'date' => date('Y-m-d', strtotime($entryLog['timestamp'])),
                                    'entryTime' => date('H:i:s', strtotime($entryLog['timestamp'])),
                                    'departureTime' => null, // Salida null cuando no hay un registro de salida
                                    'hoursCompleted' => 0, // No se puede calcular hasta que haya salida
                                    'absenceReason_id' => null,
                                    'observations' => null,
                                ]);
                            }
                        } elseif (count($userLogsArr) >= 2) {
                            // Si hay dos o más registros, se asume que es un par entrada/salida
                            for ($i = 0; $i < count($userLogsArr); $i += 2) {
                                $entryLog = $userLogsArr[$i];
                                $exitLog = $userLogsArr[$i + 1] ?? null;

                                // Verificar si ya existe un registro para este par entrada/salida
                                $existingAttendance = attendance::where('file_number', $entryLog['id'])
                                    ->where('date', date('Y-m-d', strtotime($entryLog['timestamp'])))
                                    ->where('entryTime', date('H:i:s', strtotime($entryLog['timestamp'])))
                                    ->where('departureTime', $exitLog ? date('H:i:s', strtotime($exitLog['timestamp'])) : null)
                                    ->first();

                                if (!$existingAttendance) {
                                    // Crear el nuevo registro de asistencia si no existe
                                    attendance::create([
                                        'file_number' => $entryLog['id'],
                                        'date' => date('Y-m-d', strtotime($entryLog['timestamp'])),
                                        'entryTime' => date('H:i:s', strtotime($entryLog['timestamp'])),
                                        'departureTime' => $exitLog ? date('H:i:s', strtotime($exitLog['timestamp'])) : null,
                                        'hoursCompleted' => $exitLog ? $this->calculateWorkedHours($entryLog['timestamp'], $exitLog['timestamp']) : 0,
                                        'absenceReason_id' => null,
                                        'observations' => null,
                                    ]);
                                }
                            }
                        }
                    }

                    // Desconectar después de la consulta
                    $zk->disconnect();
                } else {
                    // Log de error si no se pudo conectar
                    Log::error("No se pudo conectar al dispositivo con IP: {$device['ip']}");
                    return response()->json(['error' => "No se pudo conectar al dispositivo con IP: {$device['ip']}"], 500);
                }
            } catch (\Exception $e) {
                // Manejo de excepciones generales
                Log::error("Error al procesar el dispositivo con IP: {$device['ip']} - Error: {$e->getMessage()}");
                return response()->json(['error' => 'Hubo un problema al obtener los datos del dispositivo.'], 500);
            }
        }
        return redirect()->back()->with('success', 'Base de datos actualizada');
    }

    // Función para calcular las horas trabajadas entre dos tiempos
    private function calculateWorkedHours($entryTime, $exitTime)
    {
        $entryTimestamp = strtotime($entryTime);
        $exitTimestamp = strtotime($exitTime);

        // Calcular la diferencia en segundos
        $workedSeconds = $exitTimestamp - $entryTimestamp;

        // Convertir los segundos a horas
        $workedHours = $workedSeconds / 3600;

        return round($workedHours, 2); // Retornar las horas trabajadas con 2 decimales
    }
}
