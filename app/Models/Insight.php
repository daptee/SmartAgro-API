<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Insight extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_plan',
        'date',
        'icon',
        'title',
        'description',
    ];

    public function plan(): HasOne
    {
        return $this->hasOne(Plan::class, 'id', 'id_plan');
    }
}
