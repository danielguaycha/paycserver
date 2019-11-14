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

class EmployController extends Controller
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

    public function create()
    {

        return view('employ.store', ['plazos' => $this->plazos]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100',
            'surname' => 'required|max:100',
            'phones' => 'required',
            'address' => 'required',
            'sueldo' => 'required',
            'pago_sueldo' => 'required|string',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required'
        ], ['username.unique', 'Este nombre de usuario ya está en uso']);

        DB::beginTransaction();

        $p = new Person();
        $p->name = Str::lower($request->name);
        $p->surname = Str::lower($request->surname);
        $p->phones = $request->phones;
        $p->email = $request->email;
        $p->address = $request->address;
        $p->type = Person::TYPE_EMPLOY;


        if(!$p->save()) {
            DB::rollBack();
            session()->flash('warning', 'No se ha podido registrar el empleado, revise los datos básicos');
            return back();
        }

        // user
        $u = new User();
        $u->username = $request->username;
        $u->password = bcrypt($request->password);
        $u->person_id = $p->id;
        if(!$u->save()) {
            DB::rollBack();
            session()->flash('warning', 'No se ha podido registrar las credenciales del usuario');
            return back();
        }

        // employ
        $e = new Employ();
        $e->person_id = $p->id;
        $e->sueldo = $request->sueldo;
        $e->pago_sueldo = $request->pago_sueldo;
        $e->user_id = $u->id;

        if(!$e->save()) {
            DB::rollBack();
            session()->flash('warning', 'No se ha podido registrar el empleado, revise los datos de sueldos');
            return back();
        }

        DB::commit();
        session()->flash('success', 'Empleado guardado con éxito!');
        return back();
    }

    public function store_ruta(Request $request) {
        $request->validate([
            'user_id' => 'required',
            'rotas.*' => 'required'
        ]);

        $u = User::find($request->user_id);
        if(!$u) {
            session()->flash('warning',  'No se encontró el empleado seleccionado');
            return back();
        }

        if($u->rutas()->sync($request->rutas)) {
            session()->flash('success', 'Rutas asignadas con éxito');
            return back();
        }


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
        $e = Employ::findOrFail($id);

        $request->validate([
            'name' => 'required|max:100',
            'surname' => 'required|max:100',
            'phones' => 'required',
            'address' => 'required',
            'sueldo' => 'required',
            'pago_sueldo' => 'required|string',
            'username' => 'required|string|max:50|unique:users,username,'.$e->user_id,
            'password' => 'nullable|string'
        ], ['username.unique', 'Este nombre de usuario ya está en uso']);

        DB::beginTransaction();

        $p = Person::find($e->person_id);
        $p->name = Str::lower($request->name);
        $p->surname = Str::lower($request->surname);
        $p->phones = $request->phones;
        $p->email = $request->email;
        $p->address = $request->address;


        if(!$p->save()) {
            DB::rollBack();
            session()->flash('warning', 'No se ha podido actualizar el empleado, revise los datos básicos');
            return back();
        }

        // user
        $u = User::find($e->user_id);
        $u->username = $request->username;
        if($request->has('password'))
            $u->password = bcrypt($request->password);
        if(!$u->save()) {
            DB::rollBack();
            session()->flash('warning', 'No se ha podido actualizar las credenciales del usuario');
            return back();
        }

        // employ
        $e->sueldo = $request->sueldo;
        $e->pago_sueldo = $request->pago_sueldo;

        if(!$e->save()) {
            DB::rollBack();
            session()->flash('warning', 'No se ha podido actualizar el empleado, revise los datos de sueldos');
            return back();
        }

        DB::commit();
        session()->flash('success', 'Empleado actualizado con éxito!');
        return back();
    }

    public function destroy($id)
    {
        $e = Employ::findOrFail($id);
        $e->status = Employ::STATUS_ELIMINADO;
        if($e->save()) {
            session()->flash('success', 'Empleado eliminado con éxito');
            return back();
        }

    }

    public function cancel($id) {
        $e = Employ::findOrFail($id);

        if ($e->status === Employ::STATUS_ACTIVO){
            $e->status = Employ::STATUS_INACTIVO;
            $e->user->status = User::STATUS_INACTIVE;

            $token = DB::table('oauth_access_tokens')->select('id')->orderBy('created_at', 'desc');
            $refresToken = DB::table('oauth_refresh_tokens')
                ->where('access_token_id', $token->first()->id);
            $refresToken->delete();
            $token->delete();

            $e->user->save();
        } else {
            $e->status = Employ::STATUS_ACTIVO;
            $e->user->status = User::STATUS_ACTIVE;
            $e->user->save();
        }

        if($e->save())
            session()->flash('success', 'Esta cambiado con éxito');
        else
            session()->flash('warning', 'No se ha cambiado el estado');

        return back();
    }
}
