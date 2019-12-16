<?php

namespace App\Http\Controllers\Api;

use App\Credit;
use App\Http\Controllers\ApiController;
use App\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends ApiController
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $date = Carbon::now()->format('Y-m-d');
        $only = 'all';

        if ($request->query('zone')){
            $zones = array($request->query('zone'));
        } else {
            $zones = $request->user()->rutas->last()->pluck('id');
            if(!$zones) return $this->err('Aun tienes rutas asignadas');
        }

        if($request->query('date')) {$date = $request->query('date');}
        if($request->query('only')) {$only = Str::lower($request->query('only'));}

        $payments = Payment::join('credits', 'credits.id', 'payments.credit_id')
            ->join('persons', 'persons.id', 'credits.person_id')
            ->select('credits.cobro', 'credits.address',
                'payments.id', 'payments.credit_id', 'payments.total', 'payments.status', 'payments.mora',
                'persons.name as client_name', 'persons.surname as client_surname')
            ->whereDate('payments.date', $date)->whereIn('credits.ruta_id', $zones);

        if($only === 'all') {
            $payment_diario = $payments->where('credits.cobro', Credit::COBRO_DIARIO)->get();
            $payment_semanal = $payments->where('credits.cobro', Credit::COBRO_SEMANAL)->get();
            $payment_quincenal = $payments->where('credits.cobro', Credit::COBRO_QUINCENAL)->get();
            $payment_mensual = $payments->where('credits.cobro', Credit::COBRO_MENSUAL)->get();

            return $this->ok([
                'diario' => $payment_diario,
                'semanal' => $payment_semanal,
                'quincenal' => $payment_quincenal,
                'mensual' => $payment_mensual
            ]);
        }

        if($only === 'diario') {
            return $this->showAll($payments->where('credits.cobro', Credit::COBRO_DIARIO)->get());
        }

        if($only === 'semanal') {
            return $this->showAll($payments->where('credits.cobro', Credit::COBRO_SEMANAL)->get());
        }

        if($only === 'quincenal') {
            return $this->showAll($payments->where('credits.cobro', Credit::COBRO_QUINCENAL)->get());
        }

        if($only === 'mensual') {
            return $this->showAll($payments->where('credits.cobro', Credit::COBRO_MENSUAL)->get());
        }


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

        $totales = $payments->where('status', Payment::STATUS_FINISH);

        $credit->total_pagado = $totales->sum('total');
        $credit->n_pagos = $totales->count();
        $credit->payments = $payments;

        return $this->showOne($credit);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:2,-1',
            'description' => 'nullable|string|max:100'
        ]);

        $payment = Payment::findOrFail($id);

        if($payment->status === 2) {return $this->err('Este pago ya fue procesado la fecha '.$payment->date_payment); }

        $payment->status = $request->get('status');

        if($payment->status === 2) {
            $payment->description = ($request->get('description') ? $request->get('description') : 'Cobro exitoso');
        }

        if($payment->status === -1) {
            $payment->mora =  true;
            $payment->description = ($request->get('description') ? $request->get('description') : 'Registrado con atraso o mora');
        }

        $payment->date_payment = Carbon::now()->format('Y-m-d');

        if($payment->save()) {
            return $this->showOne($payment);
        }
        else {
            return $this->err('No he podido procesar el pago');
        }
    }

    public function destroy($id)
    {
        //
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
