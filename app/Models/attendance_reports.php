<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class attendance_reports extends Model
{
    use HasFactory;

    protected $table = 'attendance_reports';

    
    protected $fillable = [
        'file_number',
        'date',
        'entryTime',
        'departureTime',
        'extraHours',
        'observations',
        'day',
        'hoursCompleted'
    ];

}
