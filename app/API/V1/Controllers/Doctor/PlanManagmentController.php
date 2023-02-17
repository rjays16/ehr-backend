<?php

namespace App\API\V1\Controllers\Doctor;

use App\API\V1\Services\EhrResponse;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Services\HIS\HisLabOrder\HisLabOrderService;
use App\Services\HIS\HisLabTracker\UpdateRefnoTracker;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Doctor\DoctorOrderService;
use App\Services\Doctor\HisServices;
use App\Services\Doctor\ReferralService;
use App\Services\User\NotificationService;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Services\HIS\HisRadOrder\HisRadOrderService;

class PlanManagmentController extends Controller
{


    public function getAllOrders(Request $request)
    {
        try {
            $service = DoctorOrderService::init($request->input('encounterNo'));

            $data = $service->getAllOrders();
            $otherData = $service->getInsuranceDetails();

            return EhrResponse::jsonResponsePure([
                'batchOrders' => $data,
                'other-data' => $otherData
            ]);
        } catch (EhrException $e) {
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }



    public function getAllReferralOrders(Request $request)
    {
        try {
            $service = ReferralService::init($request->input('id'));

            $data = $service->getAllReferrals();
            return EhrResponse::jsonResponsePure($data);
        } catch (EhrException $e) {
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


    public function finalizedOrder(Request $request)
    {
        $hisDb = DB::connection('his_mysql');
        $ehrDb = DB::connection('mysql');
        try {
            $hisDb->beginTransaction();
            $ehrDb->beginTransaction();

            // update tracker START
            $refno = HisLabOrderService::getNewTracker();
            $radRefno = HisRadOrderService::getNewRadRefno(date('Y')."000001");
            $hisDb->commit();
            // update tracker END

            // restart HIS transaction session
            $hisDb->beginTransaction();

            $encounterNo = $request->input('encounterNo');

            $service = DoctorOrderService::init($encounterNo);
            $resp = $service->finalizeOrders();
            $hasLabORders = $service->batch_has_diag_labs;
            $hasRadORders = $service->batch_has_diag_rad;
            /** The $resp['batch'] holds the finalized lab order for this batch */
            $hisOrder = HisLabOrderService::init($resp['batch'], $encounterNo);

            // biggin saving his lab orders
            $hisOrder->save($refno);

            /** The $resp['batch'] holds the finalized rad order for this batch */
            $hisOrderRad = HisRadOrderService::init($resp['batch'], $encounterNo);

            // begin saving his rad orders
            $hisOrderRad->save($radRefno);

            $resp = array_merge($resp,[
                'batchOrders' => $service->getAllOrders(),
            ]);

            if($hasLabORders){
                $notifSrv = new NotificationService;
                $hisSrv = new HisServices($service->encounter);
                $notifSrv->notifySpmcEmployees([
                    [
                        'event' => 'NewLabOrderEvent',
                        'title' => 'Diagnostic Order',
                        'message' => 'New laboratory request order!',
                        'sender_username' => auth()->user()->username,
                        'param_data' => [
                            'encounter_nr' => $encounterNo,
                            'pid' => $service->encounter->spin,
                            'patient_type' => $service->encounter->hisEncounter->typeEncounter->type,
                            'refno' => $refno
                        ],
                        'receiver_username' => $hisSrv->getReceiverNotification()
                    ]
                ]);
            }

            $hisDb->commit();
            $ehrDb->commit();
            return EhrResponse::jsonSuccess($resp['msg'], $resp);
        } catch (EhrException $e) {
           
            $hisDb->rollBack();
            $ehrDb->rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
          
        } catch (Exception $e) {
            
            $hisDb->rollBack();
            $ehrDb->rollBack();
            new EhrLogException($e, $request->all());
           
            return EhrResponse::jsonError500($e->getMessage(), [
            ], $e->getTrace());
        }
    }

    public function getTypeCharge(Request $request){

        try {
            // $service = DoctorOrderService::init("No Encounter");
            $service = new DoctorOrderService();
            // dd($service);
            $data = $service->getTypeCharge();
            return EhrResponse::jsonResponsePure($data);
        } catch (EhrException $e) {
          
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
        
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }
}
