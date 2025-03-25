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
        'phone',
        'email',
        'address',
        'coordinator_id',
        'category_id',
        'secretary_id',
        'scale_id',
        'last_checked'
    ];

    /**
     * Relación muchos a muchos con Schedule.
     */
    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_staff', 'staff_id', 'schedule_id');
    }

    public function areas()
    {
        return $this->belongsToMany(Area::class, 'staff_area', 'staff_id', 'area_id');
    }

}
