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
        'date_of_entry',
        'coordinator_id',
        'category_id',
        'secretary_id',
        'scale_id',
        'last_checked',
        'date_of_entry',
        'coordinator_id',
        'worker_status',
        'inactive_since',
        'marking',
        'collective_agreement_id'
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

    public function collective_agreement()
    {
        return $this->belongsTo(collective_agreement::class);
    }

    public function category(){
        return $this->belongsTo(category::class);

    }

    public function secretary(){
        return $this->belongsTo(secretary::class);

    }
}
