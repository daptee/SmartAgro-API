<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyPlanPublicityReport extends Model
{
    use HasFactory;

    protected $table = 'company_plan_publicities_reports';

    protected $fillable = [
        'id_company_plan_publicity',
        'cant_impressions',
        'cant_clicks',
    ];

    public function company_plan_publicity()
    {
        return $this->belongsTo(CompanyPlanPublicity::class, 'id_company_plan_publicity');
    }
}
