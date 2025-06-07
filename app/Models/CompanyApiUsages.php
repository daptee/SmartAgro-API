<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyApiUsages extends Model
{
    protected $table = 'company_api_usages';
    
    protected $fillable = [
        'id_company',
        'request_name',
        'params',
    ];

    protected $casts = [
        'params' => 'array'
    ];

    // NO TIENE UPDATES
    public $timestamps = false;

    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company');
    }

}
