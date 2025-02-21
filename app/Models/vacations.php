<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vacations extends Model
{
    protected $table = 'vacations';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'staff_id',
        'year',
        'days',
    ];

}
