<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class coordinator extends Model
{
    use HasFactory;

    protected $table = 'coordinator'; // Nombre de la tabla

    protected $fillable = ['area_id', 'staff_id'];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function area()
    {
        return $this->belongsTo(area::class, 'area_id');
    }
}
