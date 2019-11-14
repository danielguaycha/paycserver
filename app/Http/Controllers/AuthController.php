<?php

namespace App\Http\Controllers;

use App\Traits\TokenTrait;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Client;

class AuthController extends ApiController
{
    use TokenTrait;

    private $client;

    public function __construct(){
        $this->client = Client::where("password_client", true)->first();
        $this->middleware("auth:api")->only(['user', 'logout', 'refresh']);
    }

    public function login(Request $request) {

        $this->validate($request, [
            'username' => 'required',
            'password' => 'required'
        ]);

        $u = User::select('status')->where('username', $request->username)->first();
        if($u && $u->status === 0) {
            return $this->err('Acceso denegado', 401);
        }

        $requestToken = $this->issueToken($request, 'password');

        if($requestToken !== null)
            return $requestToken;
        else
            return $this->err(__('oauth.denied_access'), 401);

    }

    public function refresh(Request $request) {
        $this->validate($request, [
            'refresh_token' => 'required'
        ]);
        return $this->issueToken($request, 'refresh_token');
    }

    public function logout() {
        $accessToken = Auth::user()->token();
        DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id)
            ->update(['revoked' => true]);
        $accessToken->revoke();
        return response()->json([], 204);
    }

    public function user(Request $request) {
        $user = $request->user();
        $user->person;
        return $this->showOne($user);
    }

}
