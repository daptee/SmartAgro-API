<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertisingSpace extends Model
{
    public $timestamps = false;
    protected $table = 'advertising_spaces';
    protected $fillable = ['name', 'data'];
    protected $casts = [
        'data' => 'array',
    ];
}

