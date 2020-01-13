<?php

namespace App\Http\Controllers;

use App\Credit;
use App\Employ;
use App\Person;
use App\Ruta;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployController extends ApiController
{
    public  $plazos = [
        Employ::PAGO_SEMANAL
    ];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index()
    {
        $e = Employ::join('persons', 'persons.id', 'employs.person_id')
            ->join('users', 'users.id', 'employs.user_id')
            ->select('employs.*', 'persons.name',
                'persons.surname', 'persons.phones', 'users.username')
            ->where('employs.status', '<>', Employ::STATUS_ELIMINADO)
            ->paginate(20);
        return view('employ.index', ['employs'=> $e]);
    }

    public function store(Request $request)
    {

    }



    public function assign_ruta(Request $request) {


        if($request->query('employ')){
            $employs = Employ::where('user_id', $request->query('employ'))->first();
        } else {
            $employs = Employ::where('status', Credit::STATUS_ACTIVO)->get();
        }

        $rutas = Ruta::select('name', 'id')->where('status', 1)->get();

        return view('employ.ruta', [
            'rutas' => $rutas,
            'employs' => $employs
        ]);
    }

    public function show($id)
    {
    }

    public function edit($id)
    {
        $e = Employ::join('persons', 'persons.id', 'employs.person_id')
            ->join('users', 'users.id', 'employs.user_id')
            ->select('employs.*', 'persons.name','persons.surname',
                'persons.phones', 'persons.address', 'persons.email','users.username')
            ->where('employs.status', '<>', Employ::STATUS_ELIMINADO)
            ->where('employs.id', $id)->first();

        if(!$e) abort(404);

        return view('employ.edit', ['employ'=> $e, 'plazos' => $this->plazos]);
    }

    public function update(Request $request, $id)
    {

    }

    public function destroy($id)
    {


    }


}
