<?php


namespace App\API\V1\Controllers\Doctor;

use App\API\V1\Services\EhrResponse;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Doctor\VitalSign\PreAssessmentService;
use Exception;

class VitalSignController extends Controller
{

    public function getVitalSignData(Request $request){
    	try{
	        $preAssessment = PreAssessmentService::init($request->input('encounter_no'));
	        $ret = $preAssessment->getVitalSigns();

	        return EhrResponse::jsonResponsePure($ret);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }



}