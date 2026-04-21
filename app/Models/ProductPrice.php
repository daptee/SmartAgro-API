<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "products_prices";

    protected $fillable = [
        'id_plan',
        'date',
        'data',
        'additional_info',
        'segment_id',
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

    public function segment(): HasOne
    {
        return $this->hasOne(Segment::class, 'id', 'segment_id');
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
