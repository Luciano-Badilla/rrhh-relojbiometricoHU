<?php

namespace App\Http\Controllers;

use App\Models\category;
use Illuminate\Http\Request;

class categoryController extends Controller
{
    public function list()
    {

        $categorys = category::all()->sortBy('name');

        return view('management.category_list', [
            'categorys' => $categorys
        ]);
    }

    public function add(Request $request)
    {

        $request->validate([
            'category' => 'unique:category,name' 
        ],[
            'category.unique' => 'La categoría ' .$request->category. ' ya existe'
        ]);

        $category = $request->input('category');
        
        category::create([
            'name' => $category
        ]);

        return redirect()->back()->with('success', 'Categoría ' . $category . ' agregada correctamente');
    }

    public function edit(Request $request)
    {
        $id = $request->input('id');
        $category = category::find($id);

        $category_name = $request->input('category');

        $category->update([
            'name' => $category_name,
        ]);

        return redirect()->back()->with('success', 'Categoría ' . $category_name . ' editada correctamente');
    }
}
