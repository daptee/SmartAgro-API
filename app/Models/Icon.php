<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Icon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'file_path',
        'file_name',
        'extension',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return asset($this->file_path);
    }
}
