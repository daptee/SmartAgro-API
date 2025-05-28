<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyRole extends Model
{
    protected $table = 'users_company_roles';

    protected $fillable = [
        'name',
        'status_id',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
