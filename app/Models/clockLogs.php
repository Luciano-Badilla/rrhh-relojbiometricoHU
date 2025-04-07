<?php

namespace App\Models;

use Carbon\Traits\Timestamp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class clockLogs extends Model
{
    protected $table = 'clock_logs';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'uid',
        'file_number',
        'timestamp',
        'type_id',
        'device_id',
        'marking',
        'inactive'
    ];
}
