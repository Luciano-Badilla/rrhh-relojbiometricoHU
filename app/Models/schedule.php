<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class schedule extends Model
{
    use HasFactory;

    protected $table = 'schedule';


    protected $fillable = [
        'id',
        'day',
        'startTime',
        'endTime',
    ];

    /**
     * Relación muchos a muchos con Staff.
     */
    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'schedule_staff', 'schedule_id', 'staff_id');
    }
}
