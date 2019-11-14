<?php

namespace App\Http\Controllers\Api;

use App\Credit;
use App\Http\Controllers\ApiController;
use App\Http\Services\CreditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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
        // page
        if ($request->query('page'))
            $page = $request->query('page');

        $limit = 15;
        $offset = ($page-1) * $limit;


        // id de ruta
        $route_id = $request->query('ruta');
        // ruta
        if($route_id) { $zones = [$route_id]; }
        else { $zones = $request->user()->rutas->pluck('id'); }


        $credit = Credit::join('persons', 'persons.id', 'credits.person_id')
            ->join('rutas', 'rutas.id', 'credits.ruta_id')
            ->select('credits.id', 'credits.cobro', 'credits.plazo', 'credits.person_id',
                'persons.name', 'persons.surname', 'rutas.name as ruta')
            ->where([
                ['credits.status', Credit::STATUS_ACTIVO]
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
        //
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
}
