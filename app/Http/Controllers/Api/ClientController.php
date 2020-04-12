<?php

namespace App\Http\Controllers\Api;

use App\Person;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Services\ClientService;
use App\Http\Controllers\ApiController;

class ClientController extends ApiController
{

    public $clientService;

    public function __construct()
    {
        $this->clientService = new ClientService();
        $this->middleware('auth:api');
        $this->middleware('admin')->only(['destroy', 'cancel', 'update']);
    }

    public function index(Request $request)
    {
        $page = 1;
        $limit = 10;
        // page
        if ($request->query('page'))
            $page = $request->query('page');
    
        // limit
        if($request->query('limit')) {
            $limit = $request->query('limit');
        }

        $offset = (intval($page)-1) * intval($limit);

        $c = Person::where([
            ['status', '>', Person::STATUS_DELETE]
        ])->orderBy('id', 'desc')->limit($limit)->offset($offset)->get();

        return $this->showAll($c);
    }

    public function store(Request $request)
    {
        $client = $this->clientService->store($request);

        if( $client !== null ) {
            return $this->showOne($client);
        } else {
            return $this->err('No se ha podido guardar los datos del cliente');
        }
    }

    public function show($id)
    {
        return $this->showOne(Person::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $client = $this->clientService->update($id, $request);

        if( $client !== null ) {
            return $this->success('Cliente actualizado con éxito');
        } else {
            return $this->err('No se ha podido actualizar los datos del cliente');
        }
    }

    public function destroy($id, Request $request)
    {
        $c = Person::findOrFail($id);

        if($c->status < Person::STATUS_DELETE) {
            return $this->err('No se puede eliminar esta persona');
        }

        $c->status = Person::STATUS_DELETE;
        if($c->save()) {
            return $this->success('Cliente eliminado con éxito');
        }
        return $this->err('No ha podido eliminar el cliente');
    }

    public function cancel($id)
    {
        $c = Person::findOrFail($id);
        $msg = '';
        if($c->status === Person::STATUS_DOWN) {
            $c->status = Person::STATUS_ACTIVE;
            $msg = 'ALTA';
        }        
        else {
            $c->status = Person::STATUS_DOWN;
            $msg = 'BAJA';
        }

        if($c->save()) {
            return $this->success("Cliente dado de $msg con éxito");
        }
        return $this->err('No ha podido cambiar el estado del cliente');
    }

    public function mora($id, Request $request) {
        $p = Person::findOrFail($id);
        if ($request->user()->isRoot() && $p->mora === Person::MORA){
            $p->mora = Person::NOMORA;        
        } else {
            $p->mora = Person::MORA;        
        }
        
        if($p->save()) {
            return $this->success('Estado de mora establecido');
        }

        return $this->err('No se ha podido cambiar el estado de mora');
    }

    // custom methods
    public function search(Request $request) {

        $search = $request->query('q');

        if(!$search) {
            $c = Person::select('id', 'name', 'surname', 'address', 'status', 'rank', 'mora')->limit(4)->orderBy('id', 'desc')->get();
            return $this->showAll($c);
        }

        $search = Str::upper($search);

        $c = Person::select('id', 'name', 'surname', 'address', 'status', 'rank', 'mora')
                    ->where([
                        ['id', '<>', 1],
                        ['status', '<>', Person::STATUS_DELETE],
                        ['name', 'like', '%'.$search.'%'],
                    ])
                    ->orWhere('surname', 'like', '%'.$search.'%')
                    ->limit(4)->get();

        return $this->showAll($c);
    }
}
