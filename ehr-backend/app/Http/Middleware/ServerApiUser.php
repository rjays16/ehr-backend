<?php

namespace App\Http\Middleware;

use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Exceptions\His\HisActiveResource;
use App\Services\User\UserService;
use App\User;
use Closure;
use Illuminate\Support\Facades\DB;

class ServerApiUser
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
        $personelID = $request->cookies->get('PERSONEL');
        $personelUname = $request->cookies->get('UNAME');


        $user = User::query()
            ->where('username', $personelUname)
            ->where('personnel_id', $personelID)
            ->first();

        if(!$user){
            DB::beginTransaction();
            $allowrollback = true;
            $personnel = [];


            try {
                $his = HisActiveResource::instance();
                $his->setPersonellId($personelID);
                $his->setPersonellUname($personelUname);

                $personnel = $his->getPersonelData($personelID, $personelUname);
                $userservice = new UserService();

                $person = $userservice->savePerson($personnel['data']['person_data']);
                $personel = $userservice->createPersonnel($personnel['data']['personnel_data']);
                $user = $userservice->createUser($personnel['data']['user_data']);

                DB::commit();

                $user = User::query()
                    ->where('username', $personnel['data']['user_data']['username'])
                    ->where('personnel_id', $personelID)
                    ->first();
                if(!$user){
                    $allowrollback = false;
                    throw new EhrException("User ({$personelUname} - {$personelID}) does not exist.", 500,[
                        'api_data' => $personnel,
                        'model_resp' => [$person,$personel,$user]
                    ]);
                }

            }catch (EhrException $e){
                if($allowrollback)
                    DB::rollBack();

                new EhrLogException($e, [
                    'file'=>'RESTAuth',
                    'excep_err' => $e->getMessage(),
                    'api_data' => $personnel,
                    'cred' => [$personelUname, $personelID],
                    'org_throw_datalog' => $allowrollback ? [] : $e->getRespDataJson()
                ]);

                return response()->json(
                    array_merge([
                        'data' => [],
                        'status' => false,
                        'success' => false,
                        'api_data' => $personnel,
                        'file'=>'RESTAuth'
                    ],$e->getRespDataJson())
                )->setStatusCode($e->getCode());

            } catch (\Exception $e) {

                if($allowrollback)
                    DB::rollBack();


                new EhrLogException($e, [
                    'file'=>'RESTAuth',
                    'exception_msg' =>$e->getMessage(),
                    'exception_file' =>$e->getFile(),
                    'exception_line' =>$e->getLine(),
                    'api_data' => $personnel,
                    'cred' => [$personelUname, $personelID]
                ]);

                return response()->json([
                    'data' => [],
                    'exception_msg' =>$e->getMessage(),
                    'exception_file' =>$e->getFile(),
                    'exception_line' =>$e->getLine(),
                    'status' => false,
                    'success' =>  false,
                    'saved' =>  false,
                    'msg' => "Something went wrong during authentication. ",
                    'message' => "Something went wrong during authentication. ",
                    'api_data' => $personnel,
                    'file'=>'RESTAuth'
                ])->setStatusCode(500);

            }
        }

        // set as currently sign in user
        auth()->login(
            $user
        );

        return $next($request);
    }
}
