<?php

namespace App\Http\Controllers\Api;

use App\Credit;
use App\Http\Controllers\ApiController;
use App\Payment;
use App\Person;
use App\Ruta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends ApiController
{

    public function __construct() {
        $this->middleware("auth:api");
    }

    public function index(Request $request)
    {
        $date = Carbon::now()->format('Y-m-d');
        $only = 'all';
        $src = '';

        // obtener las zonas del usuario o las de la petición
        if ($request->query('zone')){
            $zones = array($request->query('zone'));
        } else {
            if ($request->user()->isAdmin()) {
                $zones = Ruta::select('id')->where('status', Ruta::STATUS_ACTIVE)->get()->pluck('id');
            } else {
                $zones = $request->user()->rutas->last()->pluck('id');
            }
            if(!$zones) return $this->err('Aun tienes rutas asignadas');
        }

        // validar los query's de date (Fecha del cobro), only(Solo un tipo de plazo de cobro), src (Para mapas)
        if($request->query('date')) {$date = $request->query('date');}
        if($request->query('only')) {$only = Str::lower($request->query('only'));}
        if($request->query('src')) { $src = Str::lower($request->query('src'));}

        

        if($only === 'all') { // en caso de que quieran todos los plazos
   
            $payment_diario = $this->getPayments($date, $zones, Credit::COBRO_DIARIO, $src);
            $payment_semanal = $this->getPayments($date, $zones, Credit::COBRO_SEMANAL, $src);
            $payment_quincenal = $this->getPayments($date, $zones, Credit::COBRO_QUINCENAL, $src);
            $payment_mensual = $this->getPayments($date, $zones, Credit::COBRO_MENSUAL, $src);

            return $this->ok([
                'diario' => $payment_diario,
                'semanal' => $payment_semanal,
                'quincenal' => $payment_quincenal,
                'mensual' => $payment_mensual
            ]);
        }

        // en caso de que el plazo sea uno en especifico
        if($only === 'diario') {
            return $this->ok(['diario' => $this->getPayments($date, $zones, Credit::COBRO_DIARIO, $src)]);
        }

        if($only === 'semanal') {
            return $this->ok(['semanal' => $this->getPayments($date, $zones, Credit::COBRO_SEMANAL, $src)]);
        }

        if($only === 'quincenal') {
            return $this->ok(['quincenal' => $this->getPayments($date, $zones, Credit::COBRO_QUINCENAL, $src)]);
        }

        if($only === 'mensual') {
            return $this->ok(['mensual' => $this->getPayments($date, $zones, Credit::COBRO_MENSUAL, $src)]);
        }
    }

    public function getPayments ($date, $zones, $cobro = Credit::COBRO_DIARIO, $src = '') {
        // consulta base
        $payments = Payment::join('credits', 'credits.id', 'payments.credit_id')
            ->join('persons', 'persons.id', 'credits.person_id')
            ->whereDate('payments.date', $date)->whereIn('credits.ruta_id', $zones);

        if($src === 'map') { // selección solo para mapas
            $payments->select('credits.address', 'credits.geo_lon as lon', 'credits.geo_lat as lat',
                'payments.credit_id', 'payments.id', 'persons.name as client_name', 'persons.surname as client_surname');
        } else { // selección para vista normal
            $payments->select('credits.cobro', 'credits.address',
                'credits.geo_lon as lon', 'credits.geo_lat as lat',
                'credits.ref_detail', 'credits.ref_img',
                'payments.id', 'payments.credit_id', 'payments.total', 
                'payments.status', 'payments.mora', 'payments.number', 'payments.description',
                'persons.id as client_id',
                'persons.name as client_name', 'persons.surname as client_surname');
        }

        return $payments->where('credits.cobro', $cobro)->get();
    }

    public function store(Request $request)
    {

    }

    public function show($id)
    {
        // $id = credit_id
        $credit = Credit::join('persons', 'persons.id', 'credits.person_id')
            ->select('persons.name', 'persons.surname',
                'credits.id', 'credits.monto', 'credits.utilidad',
                'credits.total' , 'credits.description', 'credits.status')
            ->where('credits.id', $id)->first();

        $payments = Payment::select('id', 'total', 'status', 'mora', 'date')
            ->where('credit_id', $id)->orderBy('date', 'asc')->get();

        $totales = $payments->where('status', Payment::STATUS_PAID);

        $credit->total_pagado = $totales->sum('total');
        $credit->n_pagos = $totales->count();
        $credit->payments = $payments;

        return $this->showOne($credit);
    }

    /**
     * @param $id : credit_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByCredit($id) {
        $payments = Payment::where('credit_id', $id)           
            ->select('payments.*')
            ->orderBy('payments.date', 'asc')->get();

        $totales = $payments->where('status', Payment::STATUS_PAID);

        return $this->data([
            'pagado' =>  $totales->sum('total'),
            'n_pagos' => $totales->count(),
            'pays' => $payments,
        ]);
    }

    public function update(Request $request, $id) {
        $request->validate([
            'status' => 'required|in:2,-1',
            'description' => 'nullable|string|max:100'
        ]);

        $payment = Payment::findOrFail($id);

        if($payment->status === 2) {return $this->err('Este pago ya fue procesado la fecha '.$payment->date_payment); }

        $payment->status = $request->get('status');

        if($payment->status == 2) {
            $payment->description = ($request->get('description') ? $request->get('description') : 'Cobro exitoso');
        }

        if($payment->status == -1) {
            $payment->mora =  true;
            $payment->description = ($request->get('description') ? $request->get('description') : 'Registrado con atraso o mora');
            // Moratoria
            $this->calificar($payment->credit_id);
        }

        $payment->date_payment = Carbon::now()->format('Y-m-d');
        $payment->user_id = $request->user()->id;

        if($payment->save()) {
            return $this->showOne($payment);
        }
        else {
            return $this->err('No he podido procesar el pago');
        }
    }

    public function destroy(Request $request, $id) {

        $request->validate([
            'description' => 'required|string|max:100'
        ], [
            'description.required' => 'Ingrese el motivo para anular!'
        ]);

        $payment = Payment::findOrFail($id);

        if($payment->status !== Payment::STATUS_PAID) {
            return $this->err('Solo es posible anular pagos procesados!');
        }

        $payment->status = Payment::STATUS_ACTIVE;
        $payment->description = $request->description;

        if($payment->save()) {
            return $this->success("Anulado exitosamente");
        }
        return $this->err('No se ha podido anular el pago');
    }


    private function calificar($creditId) {
        $c = Credit::findOrFail($creditId);
        $p = Person::findOrFail($c->person_id);

        $p->rank = ($p->rank - Payment::POINT_BY_MORA);

        $p->save();
    }   

    /*

    $request->validate([
            'credit_id' => 'required|numeric',
            'total' => 'required|numeric'
        ]);

        $credit = Credit::findOrFail($request->get('credit_id'));

        // En caso de que el crédito este anulado o concluido
        if($credit->status !== Credit::STATUS_ACTIVO) { return $this->err('Este crédito no esta vigente'); }

        // En caso de que no se asigne el valor mínimo por cuota
        if(round($request->get('total'), 2) < round($credit->pagos_de, 2)) {
            return $this->err('El pago mínimo es de '.round($credit->pagos_de, 2));
        }

        $payments = Payment::select('id', 'saldo')
            ->where('credit_id', $request->get('credit_id'))
            ->where('status', Payment::STATUS_ACTIVE);

        $total_pagado = $payments->sum('total');
        $n_pagos = $payments->count();

        if($total_pagado >= $credit->total && $n_pagos > 0) {
            return $this->err('Ya se ha concluido el pago de este crédito, marque como finalizado');
        }

        $saldo_final = $credit->total -  ($total_pagado + $request->get('total'));

        // Calculo del saldo
        $p = Payment::create([
            'credit_id' => $request->get('credit_id'),
            'total' => $request->get('total'),
            'saldo' => $saldo_final,
        ]);

        $n_pagos++;
        $total_pagado = round($total_pagado + $p->total, 2);

        if($p) return $this->ok([
            'payment_id' => $p->id,
            'abono' => $p->total,
            'saldo' => round($p->saldo,2),
            'total_credito' => $credit->total,
            'total_pagado' => $total_pagado,
            'numero_pagos' => $n_pagos+1
        ]);
        else return $this->err('No se ha podido guardar el pago!');

    */

}
