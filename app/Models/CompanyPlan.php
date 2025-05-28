<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyPlan extends Model
{
    protected $table = 'company_plans';

    protected $fillable = [
        'id_company',
        'date_start',
        'date_end',
        'price',
        'data',
        'status_id',
    ];

    protected $casts = [
        'data' => 'array',
        'date_start' => 'date',
        'date_end' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
