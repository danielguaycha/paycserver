<?php

namespace App\Http\Controllers\Api;

use App\Credit;
use App\Http\Controllers\ApiController;
use App\Http\Services\CreditService;
use App\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreditApiController extends ApiController
{
    private $creditService;

    public function __construct()
    {
        $this->creditService = new CreditService();
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $page = 1;
        $wheres = [
            ['credits.status', Credit::STATUS_ACTIVO]
        ];

        // page
        if ($request->query('page'))
            $page = $request->query('page');

        $limit = 10;
        $offset = ($page-1) * $limit;


        // id de ruta
        $route_id = $request->query('ruta');

        // ruta
        if($route_id) { $zones = [$route_id]; }
        else { $zones = $request->user()->rutas->pluck('id'); }

        // plazo
        $plazo = $request->query('plazo');
        if($plazo) { array_push($wheres, ['plazo', Str::upper($plazo)]); }

        // cobro
        $cobro = $request->query('cobro');
        if($cobro) { array_push($wheres, ['cobro', Str::upper($cobro)]); }

        $credit = Credit::join('persons', 'persons.id', 'credits.person_id')
            ->join('rutas', 'rutas.id', 'credits.ruta_id')
            ->select('credits.id', 'credits.cobro', 'credits.plazo',
                'credits.person_id', 'credits.total',
                'persons.name', 'persons.surname', 'rutas.name as ruta')
            ->where($wheres)->whereIn('credits.ruta_id', $zones)
            ->orderBy('id', 'desc')
            ->limit($limit)->offset($offset)->get();

        return $this->showAll($credit);
    }


    public function search(Request $request) {
        $page = 1;
        if ($request->query('page'))
            $page = $request->query('page');

        $limit = 10;
        $offset = ($page-1) * $limit;

        $query = $request->query('q');
        $zones = $request->user()->rutas->pluck('id');

        if(!$query) return response()->json(['ok' => true, 'data'=> [] ]);

        $credit = Credit::join('persons', 'persons.id', 'credits.person_id')
            ->join('rutas', 'rutas.id', 'credits.ruta_id')
            ->select('credits.id', 'credits.cobro', 'credits.plazo', 'credits.person_id', 'credits.total',
                'persons.name', 'persons.surname', 'rutas.name as ruta')
            ->where([
                ['credits.status', Credit::STATUS_ACTIVO],
                ['persons.name', 'like', Str::lower($query).'%']
            ])->whereIn('credits.ruta_id', $zones)
            ->orderBy('id', 'desc')
            ->limit($limit)->offset($offset)->get();

        return $this->showAll($credit);
    }


    public function store(Request $request)
    {

        $credit = $this->creditService->store($request);

        if($credit instanceof Model) {
            return $this->showOne($credit);
        } else {
            return $this->err($credit);
        }
    }


    public function show($id)
    {
        $c = Credit::join('rutas', 'rutas.id', 'credits.ruta_id')
            ->join('persons', 'persons.id', 'credits.person_id')
            ->select('credits.*', 'rutas.name as ruta',
                'persons.name as client_name', 'persons.surname as client_surname', 'persons.address as client_address')
            ->where('credits.id', $id)->with('prenda')->first();
        //$file = Storage::disk('public')->get($c->ref_img);

        return $this->showOne($c);
    }


    public function update(Request $request, $id)
    {
        //
    }


    public function destroy($id)
    {
        //
    }


    public function cancel(Request $request) {

        $c = $this->creditService->cancelCredit($request);


        if ($c instanceof Model) {
            return $this->success("Crédito anulado con éxito");
        }

        return $this->err($c);
    }

    public function finish($id){
        $c = Credit::findOrFail($id);

        if($c->status !== Credit::STATUS_ACTIVO) {
            return $this->err('Este crédito ha sido marcado como anulado '.($c===Credit::STATUS_ANULADO) ? 'anulado':  'finalizado');
        }

        $payment_total = Payment::where('status', Payment::STATUS_ACTIVE)->sum('total');

        if($payment_total >= $c->total) {
            $c->status = Credit::STATUS_FINALIZADO;
            $c->save();
            return $this->success('Crédito finalizado con éxito!');
        } else {
            return $this->err('El total de los pagos es menor al total del crédito');
        }
    }
}
