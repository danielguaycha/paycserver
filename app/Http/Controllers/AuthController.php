<?php

namespace App\Http\Controllers;

use App\Traits\TokenTrait;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;

class AuthController extends ApiController
{
    use TokenTrait;

    private $client;

    public function __construct(){
        $this->client = Client::where("password_client", true)->first();
        $this->middleware("auth:api")->only(['user', 'logout', 'refresh', 'changePw']);
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

        $userId = $request->user()->id;
        $roles = $request->user()->hasRole('Admin');
        $user = User::where('users.id', $userId)->join('persons', 'users.person_id', 'persons.id')
            ->select('persons.name', 'persons.surname',  'persons.email',
                'users.id', 'users.username', 'users.person_id', 'users.status')->get()->first();
        $user->admin = $roles;

        return $this->ok($user);
    }

    public function changePw(Request $request) {
        $request->validate([
            'password' => 'required|string|min:4',
            'password_now' => 'required|string'
        ], [
            'password_now.required' => 'Ingrese su contraseña actual'
        ]);

        $u = User::findOrFail($request->user()->id);

        if (!Hash::check($request->get('password_now'), $u->password)) {
            return $this->err('La contraseña actual es incorrecta');
        }

        $u->password = Hash::make($request->get('password'));

        if($u->save()) {
            return $this->success('Contraseña cambiada con éxito');

        } else return $this->err('No se ha podido cambiar su contraseña');
    }
}
