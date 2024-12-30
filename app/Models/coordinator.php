<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class coordinator extends Model
{
    use HasFactory;

    protected $table = 'coordinator'; // Nombre de la tabla
    public function create()
    {
        $coordinators = Coordinator::all()->pluck('name', 'id'); // 'id' como valor y 'name' como texto
        return view('form', compact('coordinators'));
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
