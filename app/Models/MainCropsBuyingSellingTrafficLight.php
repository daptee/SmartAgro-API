<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MainCropsBuyingSellingTrafficLight extends Model
{
    use HasFactory;
    
    protected $table = "main_crops_buying_selling_traffic_light";

    protected $fillable = [
        'id_plan',
        'date',
        'input',
        'variable',
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

    public function inputs(): HasOne
    {
        return $this->hasOne(Input::class, 'id', 'input');
    }
}
