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
            // Validar los datos
            $request->validate([
                'staff_id' => 'required|integer',
                'day_id' => 'required|integer',
                'start_time' => 'nullable',
                'end_time' => 'nullable'
            ]);

            // Buscar el registro en schedule_staff
            $scheduleStaff = schedule_staff::where('staff_id', $request->staff_id)
                ->whereHas('schedule', function ($query) use ($request) {
                    $query->where('day_id', $request->day_id);
                })->first();

            // Si el horario es nulo, eliminar el schedule_staff y salir
            if (!$request->start_time || !$request->end_time) {
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

            // Buscar si ya existe un schedule con ese day_id y shift_id
            $schedule = Schedule::where('day_id', $request->day_id)
                ->where('shift_id', $shift->id)
                ->first();

            if (!$schedule) {
                // Si ya hay un schedule para ese día pero con otro shift_id, actualizarlo
                $schedule = Schedule::where('day_id', $request->day_id)->first();
                if ($schedule) {
                    if ($schedule->shift_id != $shift->id) {
                        $schedule->update(['shift_id' => $shift->id]);
                    }
                } else {
                    // Si no hay schedule para ese día, crearlo
                    $schedule = Schedule::create([
                        'day_id' => $request->day_id,
                        'shift_id' => $shift->id
                    ]);
                }
            }

            // Crear o actualizar schedule_staff
            if (!$scheduleStaff) {
                schedule_staff::create([
                    'staff_id' => $request->staff_id,
                    'schedule_id' => $schedule->id
                ]);
            } else {
                $scheduleStaff->update(['schedule_id' => $schedule->id]);
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
