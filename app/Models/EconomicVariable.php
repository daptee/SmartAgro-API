<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EconomicVariable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'economic_variables';

    protected $fillable = [
        'name',
        'status_id',
    ];

    protected $dates = ['deleted_at'];

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }
}
