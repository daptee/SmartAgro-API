<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'status_id'
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
