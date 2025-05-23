<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class absenceReason extends Model
{
    use HasFactory;

    // Si los nombres de las columnas en la base de datos son diferentes a los predeterminados, puedes especificarlas aquí
    protected $fillable = [
        'name',
        'article',
        'subsection',
        'item',
        'enjoyment',
        'year',
        'month',
        'continuous',
        'businessDay',
        'decree',
        'logical_erase'
    ];

    public function nonAttendances()
    {
        return $this->hasMany(NonAttendance::class);
    }
}
