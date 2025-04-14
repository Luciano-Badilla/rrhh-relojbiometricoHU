<?php

namespace App\Http\Controllers;

use App\Models\Vacations;
use Illuminate\Http\Request;

class VacationController extends Controller
{

    public function update(Request $request, $id)
    {
        $vacation = Vacations::findOrFail($id);
        $vacation->days = $request->input('days');
        $vacation->save();

        return response()->json(['message' => 'Días actualizados con éxito.']);
    }
}
