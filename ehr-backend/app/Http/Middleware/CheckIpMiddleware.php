<?php

   

namespace App\Http\Middleware;

use Closure;
use App;
use App\Exceptions\EhrException\EhrException;
use App\API\V1\Services\EhrResponse;
use Firebase\JWT\JWT;
use App\Models\Mongo\WhitelistIp;
use Exception;

class CheckIpMiddleware
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
        

        $decoded = JWT::decode($request->bearerToken(), config('jwt.secret'), [config('jwt.algo')]);
        $decoded = collect($decoded->dta);
        
        if($decoded->get('ip') != $request->header('x-forwarded-for')){
            throw new EhrException(trans('Not authorized.'), 401);
        }

        // checking of token is still active from the system
        $data = WhitelistIp::query()
            ->whereIn('ip', [$request->header('x-forwarded-for')])
            ->where([['active', true], ['token', $request->bearerToken()]])
            ->first();

        if(is_null($data)){
            throw new EhrException(trans('Not authorized.'), 401);
        }

        return $next($request);
    }
}