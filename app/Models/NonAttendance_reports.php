<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NonAttendance_reports extends Model
{
    use HasFactory;

    // Definir el nombre de la tabla si es diferente al plural del modelo
    protected $table = 'non_attendance_reports';

    // Definir los campos que se pueden asignar masivamente
    protected $fillable = [
        'file_number',
        'date',
        'absenceReason_id',
        'observations'
    ];

    // Definir la relación con el modelo AbsenceReason
    public function absenceReason()
    {
        return $this->belongsTo(AbsenceReason::class, 'absenceReason_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'file_number', 'file_number');
    }
}
