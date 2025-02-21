<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    // Indicar la tabla si no sigue el nombre por defecto
    protected $table = 'schedule';

    // Si solo necesitas algunos campos de manera masiva (Mass Assignment)
    protected $fillable = ['day_id', 'shift_id'];

    // Relación con el modelo 'Day'
    public function day()
    {
        return $this->belongsTo(Day::class, 'day_id');
    }

    // Relación con el modelo 'Shift'
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
