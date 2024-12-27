<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NonAttendance extends Model
{
    use HasFactory;

    // Definir el nombre de la tabla si es diferente al plural del modelo
    protected $table = 'non_attendance';

    // Definir los campos que se pueden asignar masivamente
    protected $fillable = [
        'file_number',
        'date',
        'absenceReason_id',
    ];

    // Definir la relación con el modelo AbsenceReason
    public function absenceReason()
    {
        return $this->belongsTo(AbsenceReason::class, 'absenceReason_id');
    }
}
