<?php
namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


trait TokenTrait {


    public function issueToken(Request $request, $grantType, $scope = ""){

        if(!$this->client) { return null; }

        $params = [
            'grant_type' => $grantType,
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
            'scope' => $scope
        ];

        $params['username'] = $request->username ?: $request->email;

        $request->request->add($params);
        $proxy = Request::create('/api/oauth/token', 'POST');
        return Route::dispatch($proxy);
    }

}
