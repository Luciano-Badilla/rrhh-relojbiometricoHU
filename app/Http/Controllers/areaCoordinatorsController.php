<?php

namespace App\Http\Controllers;

use App\Models\absenceReason;
use App\Models\area;
use App\Models\coordinator;
use App\Models\office;
use App\Models\staff;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class areaCoordinatorsController extends Controller
{
    public function list()
    {
        $areas = area::all()->map(function ($area) {
            $area->coordinator = coordinator::where('area_id', $area->id)->first()->staff->name_surname ?? 'Sin coordinador';
            $area->coordinator_id = coordinator::where('area_id', $area->id)->first()->staff->id ?? null;
            return $area;
        });

        $staff = staff::all()->sortBy('name_surname');

        return view('management.areaCoordinators_list', [
            'areas' => $areas->sortBy('name'),
            'staffs' => $staff
        ]);
    }

    public function add(Request $request)
    {

        $request->validate([
            'area' => 'unique:area,name',
            'coordinator' => 'unique:coordinator,staff_id',
        ], [
            'area.unique' => 'El área ya existe',
            'coordinator.unique' => 'El coordinador '.optional(optional(coordinator::where('staff_id', $request->coordinator)->first())->staff)->name_surname.' ya está asignado al área: ' .
                (coordinator::where('staff_id', $request->coordinator)->first() ? coordinator::where('staff_id', $request->coordinator)->first()->area->name : '')
        ]);

        $area = $request->input('area');
        $coordinator_id = $request->input('coordinator');

        $newArea = area::create([
            'name' => $area
        ]);

        coordinator::create([
            'area_id' => $newArea->id,
            'staff_id' => $coordinator_id
        ]);

        return redirect()->back()->with('success', 'Área ' . $area . ' agregada correctamente');
    }

    public function edit(Request $request)
    {
        $area_id = $request->input('area_id');
        $last_coordinator_id = $request->input('coordinator_id');
        $area = area::find($area_id);
        $coordinator = coordinator::where('staff_id', $last_coordinator_id)->first();
        $newAreaName = $request->input('area');
        $coordinator_id = $request->input('coordinator');

        if (
            !$coordinator ||
            $area->name != $newAreaName ||
            $coordinator->staff_id != $coordinator_id
        ) {
            $request->validate([
                'area' => 'unique:area,name,' . $area_id . ',id',
                'coordinator' => 'unique:coordinator,staff_id,' . $last_coordinator_id . ',staff_id',
            ], [
                'area.unique' => 'El área ' . $request->area . ' ya existe',
                'coordinator.unique' => 'El coordinador ' . optional(optional(coordinator::where('staff_id', $request->coordinator)->first())->staff)->name_surname .
                    ' ya está asignado al área ' . optional(optional(coordinator::where('staff_id', $request->coordinator)->first())->area)->name
            ]);

            $area = area::where('id', $area_id)->first();


            $area->update(['name' => $newAreaName]);

            coordinator::where('area_id', $area_id)->update([
                'area_id' => $area->id,
                'staff_id' => $coordinator_id
            ]);

            return redirect()->back()->with('success', 'Área ' . $newAreaName . ' editada correctamente');
        } else {

            return redirect()->back()->with('error', 'Los campos no han cambiado, el area no fue editada');
        }
    }
}
