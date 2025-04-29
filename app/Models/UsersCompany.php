<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersCompany extends Model
{
    protected $table = 'users_companies';

    protected $fillable = [
        'id_user',
        'id_company',
        'id_user_company_rol',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company');
    }

    public function rol()
    {
        return $this->belongsTo(CompanyRole::class, 'id_user_company_rol');
    }
}
