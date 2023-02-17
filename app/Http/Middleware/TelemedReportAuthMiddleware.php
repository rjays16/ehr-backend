<?php

namespace App\Http\Middleware;

use App\Exceptions\EhrException\EhrException;
use App\Services\User\UserService;
use Closure;
use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TelemedReportAuthMiddleware
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

            $service = new UserService;
            $service->telemedAuthenticateToken($request->input('token'));


        } catch (Exception $th) {
            throw new HttpException( $th->getCode(), $th->getMessage());
        }


        return $next($request);
    }
}
