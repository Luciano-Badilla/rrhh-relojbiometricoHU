<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class staff_area extends Model
{
    use HasFactory;

    protected $table = 'staff_area';

    protected $fillable = [
        'staff_id',
        'area_id',
    ];

    public function staff()
    {
        return $this->belongsTo(staff::class,'staff_id');
    }

    public function area()
    {
        return $this->belongsTo(area::class,'area_id');
    }
}
