<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceMainActiveIngredientsProducer extends Model
{
    use HasFactory;

    protected $table = "prices_main_active_ingredients_producers";

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
