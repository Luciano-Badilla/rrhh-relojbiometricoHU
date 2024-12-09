<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class attendance extends Model
{
    use HasFactory;

    // Si los nombres de las columnas en la base de datos son diferentes a los predeterminados, puedes especificarlas aquí
    protected $fillable = [
        'file_number',
        'date',
        'entryTime',
        'departureTime',
        'hoursCompleted',
        'absenceReason_id',
        'observations',
    ];

    // Relación con la tabla AbsenceReason
    public function absenceReason()
    {
        return $this->belongsTo(absenceReason::class);
    }
}
