<?php

namespace App\Http\Controllers;

use App\Http\Services\RutaService;
use App\Ruta;
use Illuminate\Http\Request;

class RutaController extends Controller
{

    private $rutaService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin')->except(['index']);
        $this->rutaService = new RutaService();
    }

    public function index(Request $request)
    {
        if($request->user()->hasRole('admin'))
            $rutas = Ruta::where('status', Ruta::STATUS_ACTIVE)->orderBy('id', 'desc')->get();
        else
            $rutas = $request->user()->rutas->where('status', Ruta::STATUS_ACTIVE);

        return view('ruta.index', ['rutas' => $rutas]);
    }

    public function create()
    {
        return view('ruta.store');
    }


    public function store(Request $request)
    {
        $r = $this->rutaService->store($request);
        if($r !== null) {
            session()->flash('success', 'Ruta registrada con éxito');
            return back();
        }
        session()->flash('warning', 'No se ha podido registrar la ruta');
        return back();
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $r = Ruta::findOrFail($id);
        return view('ruta.edit', ['ruta' => $r]);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name'=> 'required|unique:rutas,name,'.$id
        ], ['name.unique' => 'Ya existe una ruta con este nombre']);

        $r = Ruta::findOrFail($id);
        $r->name = $request->name;
        $r->description = $request->description;

        if($r->save()) {
            session()->flash('success', 'Ruta actualizada con éxito');
        } else session()->flash('warning', 'No se ha podido actualizar la ruta');

        return back();
    }


    public function destroy($id)
    {
        $r = Ruta::findOrFail($id);
        $r->status = Ruta::STATUS_DELETED;
        if($r->save())
            session()->flash('success', 'Ruta eliminada con éxito');
        else
            session()->flash('warning', 'No se ha podido eliminar la ruta');

        return back();
    }
}
