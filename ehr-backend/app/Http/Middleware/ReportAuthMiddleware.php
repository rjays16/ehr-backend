<?php

namespace App\Http\Middleware;

use App\Exceptions\EhrException\EhrException;
use App\Services\User\UserService;
use Closure;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReportAuthMiddleware
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
        try {
            // $result = collect(explode('; ', $request->headers->get('cookie')))
            // ->contains(function($item, $key){
            //     $result = false;
            //     if(strpos($item,'token') !== false){
            //         $token = explode('=', $item);
            //         $service = new UserService;
            //         $service->authenticateToken($token[1]);
            //         $result = true;
            //     }
            //     return $result;
            // });

            // if($result == false){
            //     throw new EhrException('User not authorized', 401);
            // }


            $service = new UserService;
            $service->authenticateToken($request->input('token'),$request->input('user'));

            
        } catch (\Throwable $th) {
            throw new HttpException( $th->getCode(), $th->getMessage());
        }


        return $next($request);
    }
}
