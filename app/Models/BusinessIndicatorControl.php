<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessIndicatorControl extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'business_indicator_controls';

    protected $fillable = [
        'month',
        'year',
        'data',
        'additional_info',
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

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusReport::class, 'status_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function scopePublished($query)
    {
        return $query->where('status_id', 1);
    }
}
