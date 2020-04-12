<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $table = 'persons';

    const TYPE_EMPLOY = "EMPLOY";
    const TYPE_USER = "USER";
    const TYPE_CLIENT = "CLIENT";

    const STATUS_DELETE = -1;
    const STATUS_DOWN = 0;
    const STATUS_ACTIVE = 1;
    
    const MORA = 1;
    const NOMORA = 0;

    public function client() {
        return $this->hasOne('App\Client');
    }

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
