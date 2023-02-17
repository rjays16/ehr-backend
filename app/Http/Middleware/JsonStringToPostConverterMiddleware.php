<?php

namespace App\Http\Middleware;

use Closure;

class JsonStringToPostConverterMiddleware
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
        $reqData = $request->json()->all();
        if(count($reqData) > 0)
            $request->initialize(
                $request->query(),
                $reqData,
                $request->attributes->all(),
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all()
            );
            
        return $next($request);
    }
}
