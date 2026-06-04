<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'description', 'is_admin_role', 'admin_access', 'permissions_hash'];

    public function userRoles()
    {
        return $this->hasMany(UserRole::class, 'id_role');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'id_role', 'id_user');
    }

    public function modules()
    {
        return $this->belongsToMany(AdminModule::class, 'role_modules', 'id_role', 'id_module')
                    ->withPivot('actions');
    }
}
