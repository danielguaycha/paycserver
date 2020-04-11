<?php

namespace App\Http\Controllers\Api;

use App\Employ;
use App\Http\Controllers\ApiController;
use App\Person;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployController extends ApiController
{
    public function index(Request $request)
    {
        $page = 1;
        // page
        if ($request->query('page'))
            $page = $request->query('page');

        $limit = 20;
        $offset = ($page-1) * $limit;

        $e = Employ::join('persons', 'persons.id', 'employs.person_id')
            ->join('users', 'users.id', 'employs.user_id')
            ->select('employs.*', 'persons.name',
                'persons.surname', 'persons.phones', 'users.username')
            ->where('employs.status', '<>', Employ::STATUS_ELIMINADO)
            ->offset($offset)
            ->limit($limit)
            ->get();

        return $this->ok($e);
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
            'password' => 'required',
            'admin' => 'required|boolean'
        ], ['username.unique' => 'Este nombre de usuario ya está en uso']);

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
            return $this->err('No se ha podido registrar el empleado, revise los datos básicos');
        }

        // user
        $u = new User();
        $u->username = $request->username;
        $u->password = bcrypt($request->password);
        $u->person_id = $p->id;
        if(!$u->save()) {
            DB::rollBack();
            return $this->err('No se ha podido registrar las credenciales del usuario');
        }

        if($request->admin) {
            $u->assignRole('Admin');
        }

        // employ
        $e = new Employ();
        $e->person_id = $p->id;
        $e->sueldo = $request->sueldo;
        $e->pago_sueldo = $request->pago_sueldo;
        $e->user_id = $u->id;

        if(!$e->save()) {
            DB::rollBack();
            return $this->err('No se ha podido registrar el empleado, revise los datos de sueldos');
        }

        if ($request->has('zones')){
            $u->rutas()->sync($request->zones);
        }

        DB::commit();
        return $this->success('Empleado guardado con éxito!');
    }

    public function show($id)
    {
        $e = Employ::join('persons', 'persons.id', 'employs.person_id')
            ->join('users', 'users.id', 'employs.user_id')
            ->select('employs.*', 'persons.name','persons.surname',
                'persons.phones', 'persons.address', 'persons.email','users.username', 'users.id as user_id')
            ->where('employs.status', '<>', Employ::STATUS_ELIMINADO)
            ->where('employs.id', $id)->first();

        if(!$e) {
            return $this->err('No se encontró el empleado', 404);
        }

        $zones = User::find($e->user_id)->rutas->pluck('id');

        $e->zones = $zones;

        return $this->showOne($e);
    }

    /**
     * @param $id: UserId
     */
    public function showByUser($id) {
        $user = User::where('users.id', $id)
            ->join('persons', 'persons.id', 'users.person_id')
            ->select('users.username', 'users.id as user', 'persons.*')->first();

        if ($user)
            return $this->showOne($user);
        else
            return $this->err('No se encontró el empleado', 404);
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
            'password' => 'nullable|string',
            'rutas.*' => 'nullable'
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
            return $this->err( 'No se ha podido actualizar el empleado, revise los datos básicos');
        }

        // user
        $u = User::find($e->user_id);
        $u->username = $request->username;
        if($request->has('password'))
            $u->password = bcrypt($request->password);
        if(!$u->save()) {
            DB::rollBack();
            return $this->err('No se ha podido actualizar las credenciales del usuario');
        }

        // employ
        $e->sueldo = $request->sueldo;
        $e->pago_sueldo = $request->pago_sueldo;

        if(!$e->save()) {
            DB::rollBack();
            return $this->err('No se ha podido actualizar el empleado, revise los datos de sueldos');
        }

        // asignación de rutas
        if ($request->has('zones')){
            $u->rutas()->sync($request->zones);
        }

        DB::commit();
        return $this->success('Empleado actualizado con éxito!');
    }

    public function destroy($id)
    {
        $e = Employ::findOrFail($id);
        $e->status = Employ::STATUS_ELIMINADO;
        if($e->save()) {
            return $this->success('Empleado eliminado con éxito');
        }

        return $this->err('No se ha podido eliminar este empleado');
    }

    public function cancel($id) {
        $e = Employ::findOrFail($id);

        if ($e->status === Employ::STATUS_ACTIVO){
            $e->status = Employ::STATUS_INACTIVO;
            $e->user->status = User::STATUS_INACTIVE;

            $token = DB::table('oauth_access_tokens')->select('id')
                ->where('user_id', $e->user->id)
                ->orderBy('created_at', 'desc');

            if($token->first()) {
                $refresToken = DB::table('oauth_refresh_tokens')
                ->where('access_token_id', $token->first()->id);
                $refresToken->delete();
                $token->delete();
            }

            $e->user->save();
        } else {
            $e->status = Employ::STATUS_ACTIVO;
            $e->user->status = User::STATUS_ACTIVE;
            $e->user->save();
        }

        if($e->save())
            return $this->success('Estado cambiado con éxito');
        else
            return $this->err( 'No se ha cambiado el estado');

    }



    public function store_ruta(Request $request) {
        $request->validate([
            'user_id' => 'required',
            'rotas.*' => 'required'
        ]);

        $u = User::find($request->user_id);
        if(!$u) {
            return $this->err('No se encontró el empleado seleccionado', 404);
        }

        if($u->rutas()->sync($request->rutas)) {
            return $this->success('Rutas asignadas con éxito');
        }

        return $this->err('No se pudo asignar rutas a este empleado');
    }
}
