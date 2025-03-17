<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    
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
