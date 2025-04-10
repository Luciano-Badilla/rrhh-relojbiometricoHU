<?php

namespace App\Http\Controllers;
use App\Models\schedule;
use App\Models\schedule_staff;
use App\Models\shift;


use Illuminate\Http\Request;

class scheduleController extends Controller
{

    public function store(Request $request)
    {
        try {
            $request->validate([
                'staff_id' => 'required|integer',
                'day_id' => 'required|integer',
                'start_time' => 'nullable',
                'end_time' => 'nullable'
            ]);

            // Eliminar horario si no se especifica
            if (!$request->start_time || !$request->end_time) {
                $scheduleStaff = schedule_staff::where('staff_id', $request->staff_id)
                    ->whereHas('schedule', function ($query) use ($request) {
                        $query->where('day_id', $request->day_id);
                    })->first();

                if ($scheduleStaff) {
                    $scheduleStaff->delete();
                }

                return response()->json(['message' => 'Horario eliminado correctamente'], 200);
            }

            // Buscar o crear el shift
            $shift = Shift::firstOrCreate([
                'startTime' => $request->start_time,
                'endTime' => $request->end_time
            ]);

            // Buscar un schedule EXISTENTE que tenga ese day_id y shift_id
            $schedule = Schedule::where('day_id', $request->day_id)
                ->where('shift_id', $shift->id)
                ->first();

            // Si no existe, crear uno nuevo
            if (!$schedule) {
                $schedule = Schedule::create([
                    'day_id' => $request->day_id,
                    'shift_id' => $shift->id
                ]);
            }

            // Buscar si ya existe una entrada en schedule_staff para este staff y ese día
            $scheduleStaff = schedule_staff::where('staff_id', $request->staff_id)
                ->whereHas('schedule', function ($query) use ($request) {
                    $query->where('day_id', $request->day_id);
                })->first();

            if ($scheduleStaff) {
                // Actualizar schedule_id si es diferente
                if ($scheduleStaff->schedule_id !== $schedule->id) {
                    $scheduleStaff->update(['schedule_id' => $schedule->id]);
                }
            } else {
                // Crear nueva entrada
                schedule_staff::create([
                    'staff_id' => $request->staff_id,
                    'schedule_id' => $schedule->id
                ]);
            }

            return response()->json([
                'message' => 'Horario actualizado correctamente',
                'shift' => $shift,
                'schedule' => $schedule
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocurrió un error al actualizar el horario',
                'message' => $e->getMessage()
            ], 500);
        }
    } 
}
