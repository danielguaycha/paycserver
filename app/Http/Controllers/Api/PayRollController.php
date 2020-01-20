<?php

namespace App\Http\Controllers\Api;

use App\Credit;
use App\Employ;
use App\Expense;
use App\Http\Controllers\ApiController;
use App\Payment;
use App\PayRoll;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayRollController extends ApiController
{
    public function __construct() {
        $this->middleware('auth:api');
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $pr = PayRoll::join('employs', 'employs.id', 'pay_rolls.employ_id')
            ->join('persons', 'persons.id', 'employs.person_id')
            ->select('pay_rolls.id as id','pay_rolls.total', 'pay_rolls.date', 'pay_rolls.description',
                'pay_rolls.concept', 'pay_rolls.bonus', 'pay_rolls.status',
                'persons.name', 'persons.surname', 'employs.id as employ_id')        
            ->orderBy('pay_rolls.id', 'desc')
            ->limit(20)->get();

        return $this->showAll($pr);
    }

    public function store(Request $request)
    {
        
        $request->validate([
            'amount'=> 'required|numeric',
            'employ_id' => 'required',
            'concept' => 'nullable|string|max:100',
            'bonus'=> 'nullable|numeric',
            'extra'=> 'nullable|numeric',
            'discount'=> 'nullable|numeric',
            'description' => 'nullable|string|max:100',            
            'date'=> 'nullable|date_format:Y-m-d',
            'advance'=> 'boolean'
        ]);

        $e = Employ::find($request->employ_id);
        if(!$e)
            return $this->err('No se ha encontrado este empleado');        

        if($e->status === Employ::STATUS_INACTIVO)
            return $this->err('El empleado ha sido dado de baja');

        if($this->havePaysInWeek($e->id)) 
            return $this->err('Este empleado ya registra un pago esta semana');

        
        $pr = new PayRoll();
        $pr->amount = $request->get('amount');
        $pr->employ_id = $request->get('employ_id');
        
        if($request->advance)
            $pr->advance = $request->get('advance');

        if($request->advance === true)
            $pr->concept = 'ADELANTO DE SUELDO';

        if($request->discount)
            $pr->discount = $request->get('discount');    

        if($request->bonus)
            $pr->bonus = $request->get('bonus');

        if($request->extra)
            $pr->extra = $request->get('extra');

        if($request->description)
            $pr->description = $request->get('description');

        if(!$request->date)
            $pr->date = Carbon::now()->format('Y-m-d');
        else
            $pr->date = $request->get('date');

        // calculos

        $pr->total = ($pr->amount +  $pr->extra + $pr->bonus) - $pr->discount;

        if(!$pr->save()) {
            return $this->err('No se ha podido guardar el pago');
        }

        return $this->showOne($pr);
    }

    public function show($id)
    {
        $pr = PayRoll::join('employs', 'employs.id', 'pay_rolls.employ_id')
            ->join('persons', 'persons.id', 'employs.person_id')
            ->select('pay_rolls.*','persons.name', 'persons.surname', 'employs.id as employ_id')
            ->where('pay_rolls.id', $id)->first();
        return $this->showOne($pr);            
    }

    public function showInfo($employId, Request $request) 
    {
        $now = Carbon::now();    

        $weekStartDate = $now->startOfWeek()->format('Y-m-d');
        $weekEndDate = $now->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        if($request->query('start')) {
            $weekStartDate = $request->query('start');            
        }

        if($request->query('end')){
            $weekEndDate = $request->query('end');
        }

        // fechas
        if($request->query('now')) {
            $now = new Carbon($request->query('now'));   
            $weekStartDate = $now->startOfWeek()->format('Y-m-d');
            $weekEndDate = $now->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');         
        }
    

        $employ = Employ::find($employId);

        if(!$employ) return $this->err('No existe este empleado');

        // 10 ultimos pagos realizados
        if($request->query('now')) {
            $pr = PayRoll::where('employ_id', $employId)  
            ->where('status', PayRoll::STATUS_ACTIVE)
            ->whereBetween('created_at', [$weekStartDate, $weekEndDate]);            
        }
        else {
            $pr = PayRoll::where('employ_id', $employId)  
            ->where('status', PayRoll::STATUS_ACTIVE);
        }

        $pr = $pr->select('id','advance','total', 'concept', 'date')            
            ->orderBy('date', 'desc')
            ->limit(10)->get();     

    
        
        // Creditos realizados en la semana actual
        $credits = Credit::where([
            ['user_id', $employ->user_id],
            ['status', Credit::STATUS_ACTIVO],        
        ])
            ->whereBetween('created_at', [$weekStartDate, $weekEndDate])
            ->select('id', 'total', 'plazo', 'cobro', 'utilidad')
            ->get();
    
        // Gastos
        $expenses = Expense::where([
            ['user_id', $employ->user_id],
            ['status', Expense::STATUS_ACTIVO]
        ])
            ->whereBetween('created_at', [$weekStartDate, $weekEndDate])
            ->select('id', 'category', 'monto', 'date')
            ->get();
        
        // Cobros realizados
        $payments = Payment::where([
            ['user_id', $employ->user_id],
            ['status', Payment::STATUS_PAID]
        ])
            ->whereBetween('date_payment', [$weekStartDate, $weekEndDate])
            ->select('id', 'total')    
            ->sum('total');

        return $this->ok([
            'pays' => $pr, 
            'credits'=> $credits, 
            'employ'=> $employ,
            'expenses' => $expenses,
            'cobros' => $payments
        ]);
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id, Request $request)
    {   
        $request->validate([
            'description' => 'required|string|max:100'
        ], ['description.required' => 'Ingrese el motivo para anular el rol de pago']);

        $pr = PayRoll::findOrFail($id);
        $pr->description = $request->description;
        $pr->status = PayRoll::STATUS_ANULADO;

        if($pr->save()) {
            return $this->success('Rol de pago anulado con éxito!');
        }

        return $this->err('No se ha podido anular el rol del pago');
    }
    // functions
    private function havePaysInWeek($employId) {
        $now = Carbon::now();
        $weekStartDate = $now->startOfWeek()->format('Y-m-d');
        $weekEndDate = $now->endOfWeek(Carbon::SATURDAY)->format('Y-m-d');

        return PayRoll::where('employ_id', $employId)
            ->where([
                ['advance', false],
                ['status', PayRoll::STATUS_ACTIVE]
            ])
            ->whereBetween('date', [$weekStartDate, $weekEndDate])
            ->select('id')->exists();  
    }
}
