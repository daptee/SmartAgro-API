<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MagSteerIndex extends Model
{
    use HasFactory;

    protected $table = "mag_steer_index";

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

    public function plan(): HasOne
    {
        return $this->hasOne(Plan::class, 'id', 'id_plan');
    }
}