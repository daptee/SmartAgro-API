<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserRole extends Model
{
    use HasFactory;

    protected $table = 'user_roles';

    // Desactivar los timestamps automáticos (created_at y updated_at)
    public $timestamps = false; // <-- AGREGA ESTA LÍNEA

    protected $fillable = [
        'id_user',
        'id_role',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }
}
