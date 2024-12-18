<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class staff extends Model
{
    protected $table = 'staff';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'file_number',
        'name_surname',
    ];

    /**
     * Relación muchos a muchos con Schedule.
     */
    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_staff', 'staff_id', 'schedule_id');
    }
}
