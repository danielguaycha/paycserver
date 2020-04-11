<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Role;
use App\Ruta;
use Illuminate\Http\Request;

class RutaController extends ApiController
{

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('admin')->only(['store','update', 'destroy']);
    }

    public function index(Request $request)
    {
        if($request->user()->hasAnyRole([Role::ADMIN, Role::ROOT])) {
            $rutas = Ruta::select('id', 'name', 'status', 'description')
                ->where('status', Ruta::STATUS_ACTIVE)->orderBy('id', 'desc')->get();
            return $this->showAll($rutas);
        }

        return $this->showAll($request->user()->rutas->where('status', Ruta::STATUS_ACTIVE));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100|unique:rutas,name',
            'description' => 'nullable|max:150'
        ]);

        $r = Ruta::create($request->only(['name', 'description']));

        if($r->save()) {
            return $this->showOne($r);
        }
        else {
            return $this->err("No se ha podido guardar la ruta");
        }
    }

    public function show($id)
    {
        $r = Ruta::findOrFail($id);
        return $this->showOne($r);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'=> 'required|unique:rutas,name,'.($id)
        ], ['name.unique' => 'Ya existe una ruta con este nombre']);


        $r = Ruta::findOrFail($id);
        $r->name = $request->get('name');
        if($request->get('description'))
            $r->description = $request->description;

        if($r->save()) {
            return $this->showOne($r);
        }
        return $this->err('No se ha podido actualizar la ruta');
    }

    public function destroy($id)
    {
        $r = Ruta::findOrFail($id);
        $r->status = Ruta::STATUS_DELETED;
        if($r->save())
            return $this->success('Ruta eliminada con éxito');
        else
            return $this->err( 'No se ha podido eliminar la ruta');
    }
}
