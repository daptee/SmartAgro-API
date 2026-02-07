<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActiveIngredient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "active_ingredients";

    protected $fillable = [
        'name',
        'abbreviated_name',
        'segment_id',
    ];

    protected $dates = ['deleted_at'];

    public function segment()
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }
}
