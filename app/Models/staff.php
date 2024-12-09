<?php

namespace App\Models;

use Carbon\Traits\Timestamp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class staff extends Model
{
    protected $table = 'staff';

    public $timestamps = true;

    protected $fillable = [
        'file_number',
        'name_surname'
    ];
}
