<?php

namespace App\Http\Middleware;

use Closure;
use App;
use App\Exceptions\EhrException\EhrException;
use Tymon\JWTAuth\JWTAuth;
use App\Services\User\UserService;
use Symfony\Component\HttpFoundation\Session\Session;
use Firebase\JWT\JWT;

class MobileTokenValidation
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

        $auth = App::make(JWTAuth::class);
        $token = $auth->getToken();

        if (!$token) {
            throw new EhrException(trans('auth.token_absent'), 401);
        }

        $service = new UserService;
        $decoded = JWT::decode($token->get(), config('jwt.secret'), [config('jwt.algo')]);
        $decoded = collect($decoded->dta);
        $service->authenticateToken($token->get());

        $user = auth()->user();
        $request->merge(['user' => $user ]);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $_SESSION['token'] = $token->get();

        return $next($request);
    }
}
