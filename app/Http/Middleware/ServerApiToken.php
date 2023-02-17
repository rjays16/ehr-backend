<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ServerApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(\Illuminate\Http\Request $request, Closure $next)
    {
        if($request->cookies->get('TOKEN') !== env('HIS_EHR_TOKEN', 'not defined')){
            return response()->json([
                'code' => 401,
                'status' => false,
                'success' =>  false,
                'saved' =>  false,
                'message' => 'Un Authorized.',
                'data' => []
            ])->setStatusCode(401);
        }

        return $next($request);
    }
}
