<?php

namespace App\Http\Services;

use App\Credit;
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
        ], $this->messages());


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
        $c->f_inicio = Carbon::now()->format('Y-m-d');
        $c->f_fin = Carbon::now()->addDays(Credit::diasPlazo($c->plazo))->format('Y-m-d');

        // cálculos
        $c->total_utilidad = ($c->monto * ($c->utilidad/100));
        $c->total = $c->monto + $c->total_utilidad;

        $pagos_y_plazo = $this->getNumPaysForTerms($c->plazo, $c->total, $c->cobro);
        $c->pagos_de = $pagos_y_plazo['total'];

        // Descripción
        $c->description = ( round($pagos_y_plazo['pagos'], 2)).' pago(s) de '.$c->pagos_de.' | Duración: '.$c->plazo;

        if($c->save()) {
            $this->storePrenda($request, $c->id);
            DB::commit();
            return $c;
        } else {
            DB::rollBack();
            return "No se ha podido procesar el crédito";
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

    public function cancelCredit(Request $request){

        $request->validate([
            'id' => 'required'
        ], ['id.required' => 'El crédito a anular es requerido']);

        $c = Credit::findOrFail($request->get('id'));

        if( $c->user_id !== $request->user()->id ) {
            return 'No tienes permiso para realizar esta acción';
        }

        $c->status = Credit::STATUS_ANULADO;
        if ($c->save()) {
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

    private function getNumPaysForTerms($plazo, $mount, $cobro) {
        $diasPlazo = Credit::diasPlazo($plazo);
        $diasCobro = Credit::diasCobro($cobro);
        $numPagos = intval( $diasPlazo / $diasCobro );

        return ['total' => ($mount / $numPagos), 'pagos'=> $numPagos ];
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
