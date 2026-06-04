<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'referral_code',
        'referred_by',
        'id_plan',
        'is_debtor',
        'grace_period_used',
        'event_id',
        'email_confirmation',
        'plan_start_date',
        'subscription_type',
        'free_trial_used',
        'subscription_manual',
        'admin_access',
        'last_activity_at',
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
            'is_debtor' => 'boolean',
            'grace_period_used' => 'boolean',
            'free_trial_used' => 'boolean',
            'plan_start_date' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public const DATA_WITH_ALL = ['locality', 'country', 'profile', 'plan', 'status', 'event'];

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

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'id_user', 'id_role');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        // Solo cargar módulos si el usuario no es superadmin (optimización)
        $this->load('roles');
        $roles = $this->roles;

        $isSuperAdmin = $roles->contains('is_admin_role', true);

        if ($isSuperAdmin) {
            $allowedModules = ['*'];
        } else {
            $this->load('roles.modules');
            $allowedModules = $roles
                ->flatMap(fn($role) => $role->modules->pluck('slug'))
                ->unique()
                ->values()
                ->toArray();
        }

        // Mapa de hashes por rol — usado por CheckPermissionsHash en cada request
        $rolesPermissionsHash = $roles
            ->mapWithKeys(fn($role) => [(string) $role->id => $role->permissions_hash])
            ->toArray();

        // Claims mínimos: solo escalares + los dos arrays que usan los middlewares/frontend
        // Los objetos relacionales (plan, locality, profile, etc.) se obtienen vía /user/me
        return [
            'id'                     => $this->id,
            'name'                   => $this->name,
            'last_name'              => $this->last_name,
            'email'                  => $this->email,
            'profile_picture'        => $this->profile_picture,
            'locality_name'          => $this->locality_name,
            'province_name'          => $this->province_name,
            'allowed_modules'        => $allowedModules,
            'roles_permissions_hash' => $rolesPermissionsHash,
        ];
    }

}
