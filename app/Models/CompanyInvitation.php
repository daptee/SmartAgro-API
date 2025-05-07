<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyInvitation extends Model
{
    use HasFactory;

    protected $table = 'company_invitations';

    protected $fillable = [
        'id_company',
        'mail',
        'id_user_company_rol',
        'invitation_date',
        'status_id'
    ];

    protected $casts = [
        'invitation_date' => 'datetime',
    ];

    // Relación con la empresa
    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company');
    }

    // Relación con el rol
    public function rol()
    {
        return $this->belongsTo(CompanyRole::class, 'id_user_company_rol');
    }

    public function status()
    {
        return $this->belongsTo(StatusInvitation::class, 'status_id');
    }
}
