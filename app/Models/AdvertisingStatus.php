<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertisingStatus extends Model
{
    public $timestamps = false;
    protected $table = 'advertising_status';
    protected $fillable = ['status_name'];
}

