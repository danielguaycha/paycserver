<?php

namespace App\Http\Services;

use App\Ruta;
use Illuminate\Http\Request;

class RutaService {


    public function store(Request $request) {
        $request->validate([
            'name' => 'required|max:100',
            'description' => 'nullable|max:150'
        ]);

        $r = Ruta::create($request->only(['name', 'description']));

        if($r->save()) {
            return $r;
        } else {
            return null;
        }
    }

}
