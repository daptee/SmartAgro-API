<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrainQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'periodo',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'json',
        ];
    }
}
