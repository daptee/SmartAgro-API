<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PitIndicator extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "pit_indicators";

    protected $fillable = [
        'id_plan',
        'date',
        'icon',
        'data',
        'additional_info',
        'month',
        'year',
        'status_id',
        'id_user',
    ];

    protected $dates = ['deleted_at'];

    protected function casts(): array
    {
        return [
            'data'            => 'json',
            'additional_info' => 'json',
        ];
    }

    public function plan(): HasOne
    {
        return $this->hasOne(Plan::class, 'id', 'id_plan');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusReport::class, 'status_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
