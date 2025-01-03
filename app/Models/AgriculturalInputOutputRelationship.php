<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgriculturalInputOutputRelationship extends Model
{
    use HasFactory;

    protected $table = "agricultural_input_output_relationship";

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
