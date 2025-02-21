<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class shift extends Model
{
    use HasFactory;

    // Indicar la tabla si no sigue el nombre por defecto
    protected $table = 'shifts';

    // Si solo necesitas algunos campos de manera masiva (Mass Assignment)
    protected $fillable = ['startTime', 'endTime'];

    // Relación con el modelo 'Schedule'
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'shift_id');
    }
}
