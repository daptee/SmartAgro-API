<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'phone',
        'password',
        'id_locality',
        'id_country',
        'id_user_profile',
        'id_status',
        'profile_picture',
        'locality_name',
        'province_name',
        'id_plan'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    const DATA_WITH_ALL = ['locality', 'country', 'profile', 'plan', 'status'];

    public static function getAllDataUser($id)
    {
        return User::with(User::DATA_WITH_ALL)->find($id);
    }

    public function locality(): HasOne
    {
        return $this->hasOne(Locality::class, 'id', 'id_locality');
    }

    public function country(): HasOne
    {
        return $this->hasOne(Country::class, 'id', 'id_country');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'id', 'id_user_profile');
    }

    public function plan(): HasOne
    {
        return $this->hasOne(Plan::class, 'id', 'id_plan');
    }

    public function status(): HasOne
    {
        return $this->hasOne(UserStatus::class, 'id', 'id_status');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
 
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'locality' => $this->locality,
            'profile' => $this->profile,
            'plan' => $this->plan,
            'status' => $this->status, 
            'profile_picture' => $this->profile_picture,
            'locality_name' => $this->locality_name,
            'province_name' => $this->province_name,
            'country' => $this->country
            // 'user_type' => $this->user_type,
            // 'email_confirmation' => $this->email_confirmation
        ];
    }
}
