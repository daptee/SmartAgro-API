<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'short_name',
        'description',
        'id_parent_classification',
        'id_icon',
        'status_id',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function icon()
    {
        return $this->belongsTo(Icon::class, 'id_icon');
    }

    public function parent()
    {
        return $this->belongsTo(Classification::class, 'id_parent_classification');
    }

    public function children()
    {
        return $this->hasMany(Classification::class, 'id_parent_classification');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'id_classification');
    }
}
