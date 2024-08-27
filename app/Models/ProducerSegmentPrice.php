<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProducerSegmentPrice extends Model
{
    use HasFactory;

    protected $table = "producer_segment_prices";

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
