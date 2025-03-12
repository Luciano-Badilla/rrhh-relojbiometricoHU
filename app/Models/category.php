<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class category extends Model
{
    use HasFactory;
    protected $table = 'category'; // Nombre de la tabla
    public function create()
    {
        $categories = Category::all()->pluck('name', 'id'); // 'id' como valor y 'name' como texto
        return view('form', compact('categories'));
    }

    
}
