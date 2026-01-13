<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Crop extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "crops";

    protected $fillable = [
        'name',
        'icon',
    ];

    protected $dates = ['deleted_at'];
}
