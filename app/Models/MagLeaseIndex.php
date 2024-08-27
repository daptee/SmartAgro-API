<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MagLeaseIndex extends Model
{
    use HasFactory;

    protected $table = "mag_lease_index";

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
