<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LivestockInputOutputRatio extends Model
{
    use HasFactory;

    protected $table = "livestock_input_output_ratio";

    protected $fillable = [
        'id_plan',
        'date',
        'month',
        'region',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'json',
        ];
    }

    public function plan(): HasOne
    {
        return $this->hasOne(Plan::class, 'id', 'id_plan');
    }
}
