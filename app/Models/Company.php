<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'company_name',
        'cuit',
        'logo',
        'main_color',
        'secondary_color',
        'email',
        'id_locality',
        'id_company_category',
        'range_number_of_employees',
        'website',
        'status_id'
    ];

    public function category()
    {
        return $this->belongsTo(CompanyCategory::class, 'id_company_category');
    }

    public function locality()
    {
        return $this->belongsTo(Locality::class, 'id_locality');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function plan()
    {
        return $this->hasOne(CompanyPlan::class, 'id_company');
    }
}

