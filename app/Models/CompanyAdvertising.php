<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyAdvertising extends Model
{
    public $timestamps = false;
    protected $table = 'companies_advertisings';

    protected $fillable = [
        'id_advertising_space',
        'id_company',
        'date_start',
        'date_end',
        'price',
        'file',
        'link',
        'additional_data',
        'id_advertising_status',
    ];

    protected $casts = [
        'additional_data' => 'array',
        'date_start' => 'date',
        'date_end' => 'date',
    ];

    // Relaciones

    public function advertising_space()
    {
        return $this->belongsTo(AdvertisingSpace::class, 'id_advertising_space');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company');
    }

    public function status()
    {
        return $this->belongsTo(AdvertisingStatus::class, 'id_advertising_status');
    }
}
