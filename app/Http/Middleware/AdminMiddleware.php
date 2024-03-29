<?php

namespace App\Http\Middleware;

use App\Role;
use Closure;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{

    public function handle($request, Closure $next)
    {
        if (Auth::guest()) {
            if (!$request->expectsJson())
                return redirect('/login');
            else
                return response()->json(['ok'=> false, 'message'=> 'No autenticado, Inicie sesión para continuar', 401]);
        }

        if (!$request->user()->hasAnyRole([Role::ADMIN, Role::ROOT])) {
            return response()->json(['ok' => false, 'message' => 'Necesitas permisos de administrador'], 403);
        }

        return $next($request);
    }
}
