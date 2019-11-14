<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Services\RutaService;
use App\Ruta;
use Illuminate\Http\Request;

class RutaApiController extends ApiController
{

    private $routeService;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->routeService = new RutaService();
    }

    public function index(Request $request)
    {       
        return $this->showAll($request->user()->rutas->where('status', Ruta::STATUS_ACTIVE));
    }


    public function store(Request $request)
    {
        $r = $this->routeService->store($request);
        if($r) {
            return $this->showOne($r);
        }
        else {
            return $this->err("No se ha podido guardar la ruta");
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
}
