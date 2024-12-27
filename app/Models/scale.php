<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class scale extends Model
{
    use HasFactory;
    protected $table = 'scale'; // Nombre de la tabla
    public function create()
    {
        $scales = Scale::all()->pluck('name', 'id'); // 'id' como valor y 'name' como texto
        return view('form', compact('scales'));
    }
}
