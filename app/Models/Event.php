<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'name',
        'date',
        'provinces_id',
        'localities_id',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'event_id', 'id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'provinces_id', 'id');
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class, 'localities_id', 'id');
    }
}
