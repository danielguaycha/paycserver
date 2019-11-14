<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    public $timestamps = false;

    const STATUS_ANULADO = 0;
    const STATUS_ACTIVO = 1;
    const STATUS_FINALIZADO = 2;

    const PLAZO_SEMANAL = 'SEMANAL';
    const PLAZO_QUINCENAL = 'QUINCENAL';
    const PLAZO_MENSUAL = 'MENSUAL';
    const PLAZO_MES_Y_MEDIO = 'MES_Y_MEDIO';
    const PLAZO_OOS_MESES = 'DOS_MESES';

    const COBRO_DIARIO = 'DIARIO';
    const COBRO_SEMANAL = 'SEMANAL';
    const COBRO_QUINCENAL = 'QUINCENAL';
    const COBRO_MENSUAL = 'MENSUAL';

    public function person() {
        return $this->belongsTo('App\Person');
    }

    public static function diasPlazo($plazo) {
        switch ($plazo) {
            case self::PLAZO_SEMANAL:
                return 7;
            case self::PLAZO_QUINCENAL:
                return 15;
            case self::PLAZO_MENSUAL:
                return 30;
            case self::PLAZO_MES_Y_MEDIO:
                return 45;
            case self::PLAZO_OOS_MESES:
                return 60;
        }
    }

    public static function diasCobro($cobro){
        switch ($cobro) {
            case self::COBRO_DIARIO:
                return 1;
            case self::PLAZO_SEMANAL:
                return 7;
            case self::COBRO_QUINCENAL:
                return 15;
            case self::COBRO_MENSUAL:
                return 30;
        }
    }

}
