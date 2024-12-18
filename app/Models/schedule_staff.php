<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class schedule_staff extends Model
{
    use HasFactory;

    protected $table = 'schedule_staff';


    protected $fillable = [
        'staff_id',
        'schedule_id'
    ];

    /**
     * Relación con el modelo Staff.
     * Un registro en esta tabla pertenece a un Staff.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Relación con el modelo Schedule.
     * Un registro en esta tabla pertenece a un Schedule.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
