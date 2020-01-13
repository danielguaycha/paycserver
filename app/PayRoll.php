<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayRoll extends Model
{
    public $timestamps = false;

    const PAGO_SUELDO = 'PAGO SUELDO';
    const PAGO_ADELANTO = 'ADELANTO DE SUELDO';

    const STATUS_ACTIVE = 1;
    const STATUS_ANULADO = -1;
}
