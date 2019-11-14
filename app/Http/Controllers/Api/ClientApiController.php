<?php

namespace App\Http\Controllers\Api;

use App\Person;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Services\ClientService;
use App\Http\Controllers\ApiController;

class ClientApiController extends ApiController
{

    public $clientService;

    public function __construct()
    {
        $this->clientService = new ClientService();
        $this->middleware('auth:api');
    }

    public function index()
    {
        return $this->showAll(Person::all());
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
            return $this->showOne($client);
        } else {
            return $this->err('No se ha podido actualizar los datos del cliente');
        }
    }

    public function destroy($id)
    {

    }

    // custom methods
    public function search(Request $request) {

        $search = $request->query('q');

        if(!$search) {
            $c = Person::select('id', 'name', 'surname', 'address')->limit(4)->orderBy('id', 'desc')->get();
            return $this->showAll($c);
        }

        $search = Str::lower($search);

        $c = Person::select('id', 'name', 'surname', 'address')
                    ->where([
                        ['name', 'like', '%'.$search.'%'],
                        ['id', '<>', 1]
                    ])
                    ->orWhere('surname', 'like', '%'.$search.'%')       
                    ->limit(4)->get();

        return $this->showAll($c);
    }
}
