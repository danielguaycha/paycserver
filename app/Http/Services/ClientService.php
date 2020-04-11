<?php
namespace App\Http\Services;

use App\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientService {


    public function store(Request $request) {

        $request->validate([
            'name'=> 'required|max:150',
            'surname'=> 'nullable|string|max:100',
            'address' => 'required',
            'email' => 'nullable|email',
            'phones' => 'nullable|string|max:13',
            'phones_b' => 'nullable|string|max:13'
        ], $this->messages());

        $p = new Person();
        $p->name = Str::upper($request->get('name'));
        $p->surname = Str::upper($request->get('surname'));
        $p->address = $request->get('address');
        $p->phones = $request->get('phones');
        $p->phones_b = $request->get('phones_b');
        $p->email = $request->get('email');
        $p->status = 1;
        $p->type = Person::TYPE_CLIENT;

        if(!$p->save()) {
            DB::rollBack();
            return null;
        }

        return $p;
    }

    public function update($id, Request $request) {

        $request->validate([
            'name'=> 'required|max:150',
            'surname'=> 'nullable|string|max:100',
            'address' => 'string|required',
            'email' => 'nullable|email',
            'phones' => 'nullable|string|max:13',
            'phones_b' => 'nullable|string|max:13'
        ], $this->messages());


        $p = Person::findOrFail($id);
        $p->name = Str::upper($request->get('name'));
        $p->surname = Str::upper($request->get('surname'));
        $p->address = $request->get('address');
        $p->phones = $request->get('phones');
        $p->phones_b = $request->get('phones_b');
        $p->email = $request->get('email');

        if($p->isDirty()) {
            $p->save();
        }

        return $p;
    }

    public function messages() {
        return [
            'name.required' => 'El nombre del cliente es requerido',
            'term.required' => 'Es necesario definir el plazo',
            'term.in' => 'El plazo no es válido',
            'payment.required' => 'El necesario definir el tipo de cobro',
            'payment.in' => 'El tipo de cobro no es válido'
        ];
    }


}
