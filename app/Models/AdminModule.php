<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminModule extends Model
{
    public $timestamps = false;

    protected $table = 'admin_modules';

    protected $fillable = ['slug', 'name'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_modules', 'id_module', 'id_role');
    }
}
