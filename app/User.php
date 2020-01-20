<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, HasRoles;

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const ADMIN_ROLE = 'Admin';

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function person() {
        return $this->belongsTo('App\Person');
    }


    public function rutas()
    {
        return $this->belongsToMany('App\Ruta');
    }


    public function findForPassport($username)
    {
        return $this->where('username', $username)->first();
    }
}
