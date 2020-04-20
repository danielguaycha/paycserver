<?php

namespace App\Http\Services;

use App\Credit;
use App\Payment;
use App\Person;
use App\Prenda;
use App\Traits\UploadTrait;
use Carbon\Carbon;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditService {

    use UploadTrait;

    public function store(Request $request) {

        $request->validate([
            'person_id' => 'required',
            'monto'=> 'required',
            'utilidad'=> 'required',
            'plazo' => 'required|in:'.$this->getValidTerms(),
            'cobro'=> 'required|in:'.$this->getValidPays(),
            'ruta_id' => 'required',
            'description' => 'nullable|string|max:150',
            'address' => 'required|string|max:150',
            'ref_img' => 'nullable|image|mimes:jpeg,png,jpg',
            'ref_detail' => 'nullable|string|max:150',
            'prenda_img' => 'nullable|image|mimes:jpeg,png,jpg',
            'prenda_detail' => 'nullable|string|max:150',
            'geo_lat' => 'nullable|numeric',
            'geo_lon' => 'nullable|numeric',
            'f_inicio' => 'nullable|date_format:Y-m-d'
        ], $this->messages());


        if(!Person::find($request->get('person_id'))) {
            return 'El cliente seleccionado no existe';
        }

        DB::beginTransaction();

        if($this->hasActiveCredit($request->get('person_id'))){
           return "Este cliente ya tiene un crédito activo";
        }

        $c = new Credit();
        $c->monto = $request->get('monto');
        $c->utilidad = $request->get('utilidad');
        $c->plazo = $request->get('plazo');
        $c->cobro = $request->get('cobro');
        $c->status = Credit::STATUS_ACTIVO;
        $c->geo_lat = $request->get('geo_lat');
        $c->geo_lon = $request->get('geo_lon');
        $c->person_id = $request->get('person_id');
        $c->user_id = $request->user()->id;
        $c->ruta_id = $request->get('ruta_id');
        $c->address = $request->get('address');

        //referencias
        if($request->hasFile('ref_img')){
            $c->ref_img = $this->uploadOne($request->file('ref_img'), '/ref', 'public');
        }
        $c->ref_detail = $request->get('ref_detail');

        // fechas
        if(!$request->get('f_inicio')) {

            $finicio = Credit::diasInicio($c->cobro);

            $c->f_inicio = $finicio->format('Y-m-d');
            $c->f_fin = Credit::dateEnd(Credit::diasPlazo($c->plazo), $finicio)->format('Y-m-d');

        }
        else{
            $finicio = Credit::diasInicio($c->cobro, $request->get('f_inicio'));

            $c->f_inicio = $finicio->format('Y-m-d');
            $c->f_fin = Credit::dateEnd(Credit::diasPlazo($c->plazo), $finicio)->format('Y-m-d');
        }

        // Cálculos
        $c->total_utilidad = ($c->monto * ($c->utilidad/100)); // utilidad
        $c->total = $c->monto + $c->total_utilidad; // total con utilidad

        //
        $calc = $this->calcCredit($c->plazo, $c->total, $c->cobro);

        $c->pagos_de = $calc['pagosDe']; // pagos de $
        $c->pagos_de_last = $calc['pagosDeLast']; // ultimo pago de $
        $c->description = $calc['description']; // descripción
        $c->n_pagos = $calc['nPagos'];


        if($c->save()) {
            $this->storePayments($c->id, $calc, $c->f_inicio, $c->f_fin, $c->cobro);
            $this->storePrenda($request, $c->id);
            DB::commit();
            return $c;
        } else {
            DB::rollBack();
            return "No se ha podido procesar el crédito";
        }
    }

    public function storePayments($credit_id, $calc, $fInit, $fEnd, $cobro){
        $date = Carbon::parse($fInit);
        $n_pagos = $calc['nPagos'];

        if ($calc['pagosDeLast'] !== 0) {
            $n_pagos=$n_pagos-1;
        }

        $pay=1;
        for($i = 0; $i < $n_pagos; $i++) {
            if ($i === 0) {
                $date_calc = $date;
            } else {
                $date_calc = Credit::addDays(Credit::diasCobro($cobro), $date);
            }
            Payment::create([
                'number' => $pay,
                'credit_id' => $credit_id,
                'total' => $calc['pagosDe'],
                'status' => Payment::STATUS_ACTIVE,
                'date' => $date_calc->format('Y-m-d'),
                'description' => 'Pendiente'
            ]);
            $date = $date_calc;
            $pay++;
        }

        if($calc['pagosDeLast'] !== 0) {
            $date_calc = Credit::addDays(Credit::diasCobro($cobro), $date);
            Payment::create([
                'number' => $pay,
                'credit_id' => $credit_id,
                'total' => $calc['pagosDeLast'],
                'status' => Payment::STATUS_ACTIVE,
                'date' => $date_calc->format('Y-m-d'),
                'description' => 'Pendiente'
            ]);
        }
    }

    public function storePrenda(Request $request, $credit_id) {
        if($request->hasFile('prenda_img') && $request->has('prenda_detail')){
            $p = new Prenda();
            $p->img = $this->uploadOne($request->file('prenda_img'), '/prenda', 'public');
            $p->detail = $request->get('prenda_detail');
            $p->credit_id = $credit_id;
            $p->save();
        }
    }

    public function cancelCredit(Request $request, $id){

        $request->validate([
            'description' => 'required|string|max:100'
        ], [
            'description.required' => 'Ingrese el motivo para anular!'
        ]);

        $c = Credit::findOrFail($id);
        $c->description = $request->get('description');

        if( $c->user_id !== $request->user()->id && !$request->user()->isAdmin() ) {
            return 'No tienes permiso para realizar esta acción';
        }

        $payments_numbers = Payment::select('id')
            ->where('credit_id', $c->id)
            ->where('status', Payment::STATUS_FINISH)->count();

        if($payments_numbers > 0) {
            return 'Este crédito tiene pagos registrados, no puede ser anulado';
        }

        $c->status = Credit::STATUS_ANULADO;
        if ($c->save()) {
            Payment::select('id')->where('credit_id', $c->id)->delete();
            return $c;
        } else {
            return "No se ha podido anular este crédito";
        }

    }

    // functions

    public function messages() {
        return [
            'person_id.required' => 'No se ha proporcionado un cliente',
            'plazo.required' => 'Es necesario definir el plazo',
            'plazo.in' => 'El plazo no es válido',
            'cobro.required' => 'El necesario definir el tipo de cobro',
            'monto.required' => 'El monto es necesario',
            'utilidad.required' => 'El porcentaje de ganancias es requerido',
            'cobro.in' => 'El tipo de cobro no es válido'
        ];
    }

    public function calcCredit($plazo, $mount, $cobro) {
        $diasPlazo = Credit::diasPlazo($plazo);
        $diasCobro = Credit::diasCobro($cobro);
        $numPagos = intval($diasPlazo / $diasCobro );
        $numPagosReal = $numPagos;

        $pagosDe = round($mount / $numPagos, 2);
        $pagosDeLast = 0;
        $totalIdeal = $pagosDe * $numPagos;

        if($totalIdeal !== $mount) {
            if($totalIdeal < $mount) {
                $diferencia = $mount - $totalIdeal;
                $pagosDeLast = round($pagosDe + $diferencia, 2);
                $numPagos--;
            } else {
                $diferencia = $totalIdeal - $mount;
                $pagosDeLast = round($pagosDe - $diferencia, 2);
                $numPagos--;
            }
        }

        if($pagosDeLast === 0) {
            $description = $numPagos.' pago(s) de '.$pagosDe;
        } else {
            $description = $numPagos.' pago(s) de '.$pagosDe.' + un pago de '.$pagosDeLast;
        }

        $description.= ' | Plazo: '.$plazo. ', Cobro: '.$cobro;

        return([
            'nPagos' => $numPagosReal,
            'pagosDe' => $pagosDe,
            'pagosDeLast' => $pagosDeLast,
            'description'=> $description]);
    }

    public function  hasActiveCredit($person_id) {
        $c = Credit::select('id')->where([
           ['person_id', $person_id],
           ['status', Credit::STATUS_ACTIVO]
        ])->first();

        return ($c!=null);
    }

    private function getValidTerms() {  // plazos validos
       return
           Credit::PLAZO_SEMANAL.','.
           Credit::PLAZO_QUINCENAL.','.
           Credit::PLAZO_MENSUAL.','.
           Credit::PLAZO_MES_Y_MEDIO.','.
           Credit::PLAZO_OOS_MESES.'';
   }

    private function getValidPays() {
       return
           Credit::COBRO_DIARIO.','.
           Credit::COBRO_MENSUAL.','.
           Credit::COBRO_SEMANAL.','.
           Credit::COBRO_QUINCENAL;
   }
}
