<?php

namespace App\API\V1\Controllers\Doctor;

use App\API\V1\Services\EhrResponse;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Doctor\DoctorOrderService;
use App\Services\Doctor\PrescriptionService;
use App\Services\Pharmacy\PharmacyService;
use Exception;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    public function searchMeds(Request $request)
    {
        try {
            $serv = new PharmacyService();
            return EhrResponse::jsonResponsePure($serv->search($request->input('q')));
        } catch (Exception $e) {
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


    public function getDefaultOptions()
    {
        try {
            $serv = new PrescriptionService();
            return EhrResponse::jsonResponsePure($serv->defaultOptions());
        } catch (Exception $e) {
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }




    


    public function savePrescription(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = ['orders' =>$request->post('orders')];
            $service = DoctorOrderService::init($request->input('encounterNo'));

            $resp = $service->saveMedicationOrders($data);
            DB::commit();
            return EhrResponse::jsonSuccess($resp['msg'], [
                'data' => $resp
            ]);
        } catch (EhrException $e) {
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


}
