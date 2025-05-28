<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertisingReport extends Model
{
    use HasFactory;

    protected $table = 'advertisings_reports';

    protected $fillable = [
        'id_company_advertising',
        'cant_impressions',
        'cant_clicks',
    ];

    public function company_advertising()
    {
        return $this->belongsTo(CompanyAdvertising::class, 'id_company_advertising');
    }
}
