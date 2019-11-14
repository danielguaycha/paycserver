<?php

namespace App\Http\Middleware;

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

        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        return $next($request);
    }
}
