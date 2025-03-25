<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class area extends Model
{
    use HasFactory;
    protected $table = 'area'; // Nombre de la tabla
    public function create()
    {
        $areas = area::all()->pluck('name', 'id'); // 'id' como valor y 'name' como texto
        return view('form', compact('areas'));
    }
    
}
