<?php

namespace App\API\V1\Controllers\Patient;

use App\API\V1\Services\EhrResponse;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Services\Patient\PatientService;
use App\Services\Person\PersonService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DeptEncounter;
use Exception;
use Illuminate\Support\Collection;
use PDO;

class PatientController extends Controller
{


    public function patientInfo(Request $request){
        try{
            $pService = new PatientService($request->input('id','NO Encounter'));
            
            return EhrResponse::jsonResponsePure($pService->getPatientInfo());

        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(),[], $e->getTrace());
        }
    }

    public function patientNurseInfo(Request $request){
        try{
            $pService = new PatientService($request->input('id','NO Encounter'));
            return EhrResponse::jsonResponsePure($pService->nursePatientInfo());
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(),[], $e->getTrace());
        }
    }


    public function patientEncounterHistory(Request $request){

        try {

            $encounters = collect(PatientService::getEncounters(PatientService::$filterByPid, $request->input('pid'))->fetchAll(PDO::FETCH_ASSOC))->recursive();

            return EhrResponse::jsonResponsePure($encounters->map(function($enc){
                /**
                 * @var Collection $enc
                 */
                $currDept = DeptEncounter::query()->where('encounter_no', $enc->get('encounter_no'))->orderByDesc('deptenc_date')->first();

                if($enc->get('encounter_date')){
                    $thisentry = new \DateTime($enc->get('encounter_date'));
                    $encounter_date = date('m-d-Y h:i a', ($thisentry->getTimestamp() * 1));
                }
                else
                    $encounter_date = "";

                if($enc->get('discharge_dt')){
                    $thisentry = new \DateTime($enc->get('discharge_dt'));
                    $discharge_dt = date('m-d-Y h:i a', ($thisentry->getTimestamp() * 1));
                }
                else
                    $discharge_dt = "";
                    
                return [
                    "encounter_no" =>  $enc->get('encounter_no'),
                    "patient_type" => $currDept->getEncounterTypeHisEquivalent(),
                    "encounter_date" =>  $encounter_date,
                    "discharge_dt" =>  $discharge_dt,
                    "deptenc_code" =>  $currDept->deptenc_code,
                    "area_desc" =>  $currDept->area ? $currDept->area->area_desc: $currDept->opArea->area_desc,
                ];
            })->toArray());
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(),[], $e->getTrace());
        }
    }
}
