<?php


namespace App\API\V1\Controllers\Auth;

use App\API\V1\Services\EhrResponse;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Services\User\UserService;
use Exception;

class LogoutController extends Controller
{

    public function logout()
    {

        try {
            $service = new UserService();
            return EhrResponse::jsonSuccess($service->logout());

        } catch (EhrException $e) {
            return EhrResponse::jsonResponse($e->getMessage(),$e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (TokenInvalidException $e) {
            new EhrLogException($e, request()->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, request()->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

}
