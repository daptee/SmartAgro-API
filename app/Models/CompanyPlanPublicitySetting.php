<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyPlanPublicitySetting extends Model
{
    protected $table = 'company_plan_publicity_settings';

    protected $fillable = [
        'id_company_plan',
        'show_any_ads',
    ];

    public function plan()
    {
        return $this->belongsTo(CompanyPlan::class, 'id_company_plan');
    }

    public function publicity()
    {
        return $this->hasMany(CompanyPlanPublicity::class, 'id_company_plan');
    }
}

