<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RainfallRecordProvince extends Model
{
    use HasFactory;

    protected $table = "rainfall_record_provinces";

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
