<?php

namespace App\Http\Middleware;

use Closure;

class ApiAuthentication
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
        
        if(auth()->guest()){
            return response()->json([
                'code' => 500,
                'status' => false,
                'success' =>  false,
                'saved' =>  false,
                'message' => 'User must be authenticated.',
                'data' => []
            ])->setStatusCode(500);
        }

        return $next($request);
    }
}
