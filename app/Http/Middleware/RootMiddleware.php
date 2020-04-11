<?php

namespace App\Http\Middleware;

use App\Role;
use Closure;
use Illuminate\Support\Facades\Auth;

class RootMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::guest()) {
            if (!$request->expectsJson())
                return redirect('/login');
            else
                return response()->json(['ok'=> false, 'message'=> 'No autenticado, Inicie sesión para continuar', 401]);
        }

        if (!$request->user()->hasRole([Role::ROOT])) {
            return response()->json(['ok' => false, 'message' => 'No tienes permisos para realizar esta acción'], 403);
        }

        return $next($request);
    }
}
