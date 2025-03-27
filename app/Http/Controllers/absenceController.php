<?php

namespace App\Http\Controllers;

use App\Models\absenceReason;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class absenceController extends Controller
{
    public function list()
    {

        $absenceReasons = absenceReason::all()->where('logical_erase', false)->map(function ($absenceReason) {
            $absenceReason->enjoyment = $absenceReason->enjoyment ? 'Si' : 'No';
            $absenceReason->continuous = $absenceReason->continuous ? 'Si' : 'No';
            $absenceReason->businessDay = $absenceReason->businessDay ? 'Si' : 'No';

            return $absenceReason;
        });

        return view('management.absenceReason_list', [
            'absenceReasons' => $absenceReasons->sortBy('name')
        ]);
    }

    public function add(Request $request)
    {
        $description = $request->input('description');
        $decree = $request->input('decree') ?? '-';
        $article = $request->input('article') ?? '-';
        $subsection = $request->input('subsection') ?? '-';
        $item = $request->input('item') ?? '-';
        $year = $request->input('year') ?? '-';

        $enjoyment = $request->input('enjoyment');
        $businessDay = $request->input('businessDay');
        $continuous = $request->input('continuous');

        $existe = absenceReason::where('name', $description)
            ->where('decree', $decree)
            ->exists();

        if ($existe) {
            return redirect()->back()->with('error', 'Ya existe la inasistencia ' . $description . ' con el decreto ' . $decree);
        }

        absenceReason::create([
            'name' => $description,
            'decree' => $decree,
            'article' => $article,
            'subsection' => $subsection,
            'item' => $item,
            'year' => $year,
            'enjoyment' => $enjoyment,
            'businessDay' => $businessDay,
            'continuous' => $continuous,
        ]);

        return redirect()->back()->with('success', 'Inasistencia ' . $description . ' agregada correctamente');
    }

    public function edit(Request $request)
    {
        $id = $request->input('id');
        $description = $request->input('description');
        $decree = $request->input('decree') ?? '-';
        $article = $request->input('article') ?? '-';
        $subsection = $request->input('subsection') ?? '-';
        $item = $request->input('item') ?? '-';
        $year = $request->input('year') ?? '-';

        $enjoyment = $request->input('enjoyment');
        $businessDay = $request->input('businessDay');
        $continuous = $request->input('continuous');

        absenceReason::find($id)->update([
            'name' => $description,
            'decree' => $decree,
            'article' => $article,
            'subsection' => $subsection,
            'item' => $item,
            'year' => $year,
            'enjoyment' => $enjoyment,
            'businessDay' => $businessDay,
            'continuous' => $continuous,
        ]);

        return redirect()->back()->with('success', 'Inasistencia ' . $description . ' editada correctamente');
    }

    public function delete(Request $request)
    {
        $id = $request->input('id');
        $description = $request->input('description');

        absenceReason::find($id)->delete();

        return redirect()->back()->with('success', 'Inasistencia ' . $description . ' eliminada correctamente');
    }
}
