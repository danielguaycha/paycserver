<?php

namespace App;

use Carbon\Carbon;
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

    public function prenda() {
        return $this->hasMany('App\Prenda');
    }

    public static function diasInicio($cobro, $init = null) {
        $dias = Credit::diasCobro($cobro);
        if ($init === null)
            return Credit::addDays($dias);
        else
            return Credit::addDays($dias, $init);
    }

    public static function addDays2($days = 1, $date = null) {        
        if(!$date) {
            $d = Carbon::now();
        }
        else {
            $d = Carbon::parse($date);
        }

        if($d->isSaturday()){
            $d = $d->addDays(2);
            echo '-- es sabado add 2 ---';
            if ($days === 1)
                $days--;
        }

        else if ($d->isSunday()) {
            $d = $d->addDays(1);
            echo '-- es domingo add 1 ---';
            if ($days === 1)
                $days--;
        }
    
        else if(!$d->isSaturday() && !$d->isSunday() && $days === 1) {            
            echo '-- agregamos 1 ---';
            $d = $d->addDay();
        }

        //echo "------".$d->format('Y-m-d').'--------';
        //echo " ---- dias hasta aqui $days ----";
        if($days>= 15 && $days < 30) {
            $days = $days - 2;            
        }

        if($days >= 30) {
            $days = $days - 3;            
        }

        for($i = 1; $i<$days; $i++) {
            $d = $d->addDay();
            if($d->isSaturday()){
                $d = $d->addDay();
            }
        }
        return $d;
        
        /*if ($d->isSunday()) {
            $d = $d->addDays($days+1);
        }
        else {
            $d = $d->addDays($days);
        }
        return $d;*/
    }

    public static function addDays($days = 1, $date = null) {
        if(!$date) {
            $d = Carbon::now();
        }
        else {
            $d = Carbon::parse($date);
        }
        switch($days) {
            case 1: 
                if($d->isSaturday()){
                    $d = $d->addDays(2);
                }
                else if ($d->isSunday()) {
                    $d = $d->addDays(1);                                                            
                }    
                else if(!$d->isSaturday() && !$d->isSunday()) {                                
                    $d = $d->addDay();
                }
            break;
            case 7:
                $dateInit = self::remplaceWeekend($d);
                $d = $dateInit->addDays(7);                
            break;
            case 15:
                $dateInit = self::remplaceWeekend($d);
                $d = $dateInit->addDays(15);  
            break;
            case 30:
                $dateInit = self::remplaceWeekend($d);
                $d = $dateInit->addDays(30);  
            break;
        }
        return self::remplaceWeekend($d);
    }

    public static function  remplaceWeekend ($date) {
        $d = Carbon::parse($date);        
        if($d->isSunday()) {
            $d = $d->addDays(1);                                                            
        }

        return $d;
    }

    public static function dateEnd($days=7, $date) {
        $d = Carbon::parse($date);    

        if($d->isSaturday()){
            $d = $d->addDays(2);
        }

        for($i = 1; $i<$days; $i++) {
            $d = $d->addDay();
            if($d->isSaturday()){
                $d = $d->addDay();
            }
        }
        return $d;
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
