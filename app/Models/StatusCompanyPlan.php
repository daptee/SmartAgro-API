<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusCompanyPlan extends Model
{
    protected $fillable = ['name', 'description'];

    protected $table = 'status_company_plan';
}
