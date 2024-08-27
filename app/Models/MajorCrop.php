<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MajorCrop extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_plan',
        'date',
        'icon',
        'data',
    ];
    
    protected function casts(): array
    {
        return [
            'data' => 'json',
        ];
    }
}
