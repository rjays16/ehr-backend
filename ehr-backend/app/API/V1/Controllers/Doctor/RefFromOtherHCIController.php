<?php

namespace App\API\V1\Controllers\Doctor;

use App\API\V1\Services\EhrResponse;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Doctor\HCIServices;
use Exception;

class RefFromOtherHCIController extends Controller
{
    public function getData(Request $request)
    {
        try{
            return EhrResponse::jsonResponsePure(HCIServices::init($request->input('id'))->getData());
        }catch(EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch(Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(),[],$e->getTrace());
        }
    }



    public function saveData(Request $request)
    {
        try{
            $resp = HCIServices::init($request->input('id'))->updateRefferedHCI([
                'reason' => $request->post('reason'),
                'hci_name' => $request->post('hci_name'),
                'isHCI' => $request->post('isHCI'),
            ]);
            return EhrResponse::jsonSuccess($resp['msg'], $resp);
        }catch(EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch(Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(),[],$e->getTrace());
        }
    }
}
