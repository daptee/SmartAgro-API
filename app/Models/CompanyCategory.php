<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyCategory extends Model
{
    protected $fillable = ['name', 'status_id'];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}

