<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['id_country', 'region', 'status_id'];

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
