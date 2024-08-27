<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainGrainPrice extends Model
{
    use HasFactory;

    protected $table = "main_grain_prices";

    protected $fillable = [
        'id_plan',
        'date',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'json',
        ];
    }

}
