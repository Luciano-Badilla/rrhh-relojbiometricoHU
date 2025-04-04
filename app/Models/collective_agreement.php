<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class collective_agreement extends Model
{
    use HasFactory;

    protected $table = 'collective_agreement';

    protected $fillable = [
        'decree',
    ];
}
