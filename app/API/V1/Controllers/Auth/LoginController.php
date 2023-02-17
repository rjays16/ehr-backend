<?php


namespace App\Api\V1\Controllers\Auth;

use App\API\V1\Services\EhrResponse;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Http\Controllers\Controller;
use App\Services\TelemedService;
use App\Services\User\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{

    public function login(Request $request)
    {

        try{
            DB::beginTransaction();

            $userserv = new UserService();
            // dd($userserv->generateToken([
            //     'ip' => 'ip address here'
            // ],now()->addYear()->timestamp));

            $resp = $userserv->loginMobileApi(
                $request->post('username'),
                $request->post('password'),
                $request->post('device_uuid'),
                $request->post('device_device_unique_id'),
                $request->post('device_platform'),
                $request->post('device_model')
            );

            (new TelemedService)->registerOneSignal($request->post('one_sig_p_id'), $request->post('one_sig_app_name'));

            DB::commit();
            return EhrResponse::jsonSuccess('Login success.', $resp);

        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }


        return EhrResponse::jsonError500('Something went wrong.');
    }

}
