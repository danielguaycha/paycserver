<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_FINISH = 2;

    public $timestamps = false;
    protected $fillable = [
        'credit_id', 'total', 'saldo', 'date',  'date_payment', 'description'
    ];
}
