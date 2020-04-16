<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LogAfterRequest
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
        return $next($request);
    }

    public function terminate($request, $response)
	{
        if(config('app.debug')) {
            $out = new \Symfony\Component\Console\Output\ConsoleOutput();
            $out->writeln($request->url());
        }
       
		//Log::info('app.requests', ['request' => $request->url()]);
	}
}
