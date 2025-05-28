<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyPlanPublicity extends Model
{
    protected $table = 'company_plan_publicities';

    protected $fillable = [
        'id_company_plan',
        'id_advertising_space',
        'gif_path',
        'is_active',
    ];

    public function plan()
    {
        return $this->belongsTo(CompanyPlan::class, 'id_company_plan');
    }

    public function advertisingSpace()
    {
        return $this->belongsTo(AdvertisingSpace::class, 'id_advertising_space');
    }
}

