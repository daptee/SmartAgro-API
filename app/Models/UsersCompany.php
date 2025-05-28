<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersCompany extends Model
{
    protected $table = 'users_companies';

    protected $fillable = [
        'id_user',
        'id_company_plan',
        'id_user_company_rol',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function plan()
    {
        return $this->belongsTo(CompanyPlan::class, 'id_company_plan');
    }

    public function rol()
    {
        return $this->belongsTo(CompanyRole::class, 'id_user_company_rol');
    }
}
