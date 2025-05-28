<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusInvitation extends Model
{
    protected $fillable = ['name'];

    protected $table = 'status_invitation';
}
