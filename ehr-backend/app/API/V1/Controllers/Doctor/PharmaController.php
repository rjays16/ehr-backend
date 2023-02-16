<?php


namespace App\API\V1\Controllers\Doctor;

use App\API\V1\Services\EhrResponse;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Services\Doctor\DrugsAndMedicine\PharmaService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;

class PharmaController extends Controller
{
    public function patientMedication(Request $request)
    {
        try {
            $pharmaService = PharmaService::init($request->post('encounter_no'));
            $meds = $pharmaService->generateMeds();
            return EhrResponse::jsonResponsePure($meds);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function defaultOptions(Request $request)
    {
        try {
            return EhrResponse::jsonResponsePure(PharmaService::defaultOptions());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function actionMedication(Request $request){
        $data = [
            "refno" => $request->post('refno'),
            "item_code" => $request->post('itemID'),
            "frequency" => $request->post('frequency'),
            "route" => $request->post('route'),
            "dosage" => $request->post('dosage') ? :'',
            "source" => $request->post('source'),
            "user_id" => Auth::user()->personnel_id,
        ];

        try {
            $pharmaService = PharmaService::init($request->post('encounter_no'));
            $meds = $pharmaService->actionMedication($data);
            $result = collect([])->put("data", $meds);
            return EhrResponse::jsonSuccess($meds['message'], $result);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function getMedication(Request $request){

        $data = [
            "refno" => $request->input('refno'),
            "item_id" => $request->input('item_id')
        ];

        try {
            $pharmaService = PharmaService::init($request->input('encounter_no'));
            $meds = $pharmaService->getMedication($data);
            return EhrResponse::jsonResponsePure($meds);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }
}