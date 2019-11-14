<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    coNST STATUS_DELETED = -1;

    protected $fillable = [
        'name', 'description'
    ];
}
