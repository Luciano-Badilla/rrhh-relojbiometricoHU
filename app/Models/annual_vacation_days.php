<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class annual_vacation_days extends Model
{
    protected $table = 'annual_vacation_days';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'staff_id',
        'days',
    ];  
}
