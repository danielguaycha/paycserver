<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employ extends Model
{
    const STATUS_ACTIVO = 1;
    const STATUS_INACTIVO = 0;
    const STATUS_ELIMINADO = -1;

    const PAGO_SEMANAL = 'PAGO SEMANAL';

    public function person() {
        return $this->belongsTo('App\Person');
    }

    public function user() {
        return $this->belongsTo('App\User');
    }
}
