<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class secretary extends Model
{
    use HasFactory;
    protected $table = 'secretary'; // Nombre de la tabla
    public function create()
    {
        $secretaries = Secretary::all()->pluck('name', 'id'); // 'id' como valor y 'name' como texto
        return view('form', compact('secretaries'));
    }
}
