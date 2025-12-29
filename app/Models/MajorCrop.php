<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MajorCrop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'month',
        'year',
        'data',
        'date',
        'icon',
        'id_plan',
        'status_id',
        'id_user',
    ];

    protected $dates = ['deleted_at'];

    protected function casts(): array
    {
        return [
            'data' => 'json',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'id_plan', 'id');
    }

    // Scope to filter published records (status_id = 1)
    public function scopePublished($query)
    {
        return $query->where('status_id', 1);
    }

    // Scope to filter by year and month range
    public function scopeDateRange($query, $yearFrom, $monthFrom, $yearTo, $monthTo)
    {
        return $query->where(function ($q) use ($yearFrom, $monthFrom) {
            $q->where('year', '>', $yearFrom)
              ->orWhere(function ($q2) use ($yearFrom, $monthFrom) {
                  $q2->where('year', $yearFrom)->where('month', '>=', $monthFrom);
              });
        })->where(function ($q) use ($yearTo, $monthTo) {
            $q->where('year', '<', $yearTo)
              ->orWhere(function ($q2) use ($yearTo, $monthTo) {
                  $q2->where('year', $yearTo)->where('month', '<=', $monthTo);
              });
        });
    }
}
