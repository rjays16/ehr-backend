<?php

namespace App\API\V1\Controllers\Doctor;

use App\API\V1\Services\EhrResponse;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Services\Doctor\Soap\SoapService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Diagnostic\DiagnosticsService;
use Exception;
use Illuminate\Support\Facades\DB;

class SoapController extends Controller
{
    public  function  getSoapData(Request $request){
        try{
            $encService = SoapService::init($request->input('id','NO ENCOUNTER PROVIDED'));
            return EhrResponse::jsonResponsePure($encService->getSoap());
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


    public  function  getSoapOtions(Request $request){
        try{
            return EhrResponse::jsonResponsePure(SoapService::subjectiveOptions());
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


    public  function  saveSoapObjective(Request $request){
        try{
            DB::beginTransaction();
            $encService = SoapService::init($request->input('id','NO ENCOUNTER PROVIDED'));
            $resp = $encService->saveObjective([
                'text-objective' => $request->post('text-objective','')
            ]);
            DB::commit();
            return EhrResponse::jsonSuccess($resp->get('msg'), $resp);
        }catch (EhrException $e){
            DB::rollBack();
            if(isset($encService))
                $encService->tracerAssesssment->ressetTracerUpdate();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            DB::rollBack();
            if(isset($encService))
                $encService->tracerAssesssment->ressetTracerUpdate();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public  function  saveSoapAsseessmentClinicalImp(Request $request){
        try{
            DB::beginTransaction();
            $encService = SoapService::init($request->input('id','NO ENCOUNTER PROVIDED'));
            $resp = $encService->saveSoapAsseessmentClinicalImp([
                'impression' => $request->post('impression','')
            ], true);

            DB::commit();
            return EhrResponse::jsonSuccess($resp->get('msg'), $resp);
        }catch (EhrException $e){
            DB::rollBack();
            if(isset($encService))
                $encService->tracerAssesssment->ressetTracerUpdate();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            DB::rollBack();
            if(isset($encService))
                $encService->tracerAssesssment->ressetTracerUpdate();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


    

    public  function  saveSoapAsseessmentDiagnosis(Request $request){
        try{
            DB::beginTransaction();


            $encService = SoapService::init($request->input('id','NO ENCOUNTER PROVIDED'));

            $respfinaldiag = $encService->saveAssessment_finalDiag([
                'text-assessment-final' => $request->post('text-assessment-final','')
            ], false);
            $respotherdiag = $encService->saveAssessment_OtherDiag([
                'text-assessment-other' => $request->post('text-assessment-other','')
            ]);

            DB::commit();
            return EhrResponse::jsonSuccess($respotherdiag->get('msg'), $respotherdiag);
        }catch (EhrException $e){
            DB::rollBack();
            if(isset($encService))
                $encService->tracerAssesssment->ressetTracerUpdate();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            DB::rollBack();
            if(isset($encService))
                $encService->tracerAssesssment->ressetTracerUpdate();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


    public  function  saveSoapPlan(Request $request){
        try{
            DB::beginTransaction();

            $encService = SoapService::init($request->input('id','NO ENCOUNTER PROVIDED'));
            $resp = $encService->savePlan([
                'text-assessment-plan' => $request->post('text-assessment-plan', '')
            ]);
            DB::commit();
            return EhrResponse::jsonSuccess($resp->get('msg'), $resp);
        }catch (EhrException $e){
            DB::rollBack();
            if(isset($encService))
                $encService->tracerAssesssment->ressetTracerUpdate();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            DB::rollBack();
            if(isset($encService))
                $encService->tracerAssesssment->ressetTracerUpdate();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


    public  function  saveSoapSubjective(Request $request){
        try{
            DB::beginTransaction();

            $encService = SoapService::init($request->input('id','NO ENCOUNTER PROVIDED'));
            $resp = $encService->saveCheifCoplaint([
                'chiefComplaint_tag' => $request->post('chiefComplaint_tag',array()),
                'chiefComplaint_others' => $request->post('chiefComplaint_others','')
            ]);
            DB::commit();
            return EhrResponse::jsonSuccess($resp->get('msg'), $resp);
        }catch (EhrException $e){
            DB::rollBack();
            if(isset($encService))
                $encService->tracerAssesssment->ressetTracerUpdate();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            DB::rollBack();
            if(isset($encService))
                $encService->tracerAssesssment->ressetTracerUpdate();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


    public  function  saveSoapAssessmentIcd10(Request $request){
        try{

            DB::beginTransaction();

            $encService = new DiagnosticsService();
            $resp = $encService->saveDiagnosis([
                'id' => $request->post('id','NO ENCOUNTER PROVIDED'),
                'alt_diagnosis' => $request->post('alt_diagnosis',''),
                'icd_code' => $request->post('icd_code','NO ICD CODE'),
            ]);

            DB::commit();
            return EhrResponse::jsonSuccess($resp->get('msg'),$resp);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


    public  function  saveSoapAssessmentIcd10List(Request $request){
        try{

            DB::beginTransaction();

            $encService = new DiagnosticsService();
            $icds = $request->post('data');

            foreach ($icds as $key => $icd) {
                $resp = $encService->saveDiagnosis([
                    'id' => $icd['id'],
                    'icd_code' => $icd['icd_code'],
                ]);
            }
           

            DB::commit();
            return EhrResponse::jsonSuccess($resp->get('msg'),$resp);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


    public  function  removeSoapAssessmentIcd10(Request $request){
        try{

            DB::beginTransaction();

            $encService = new DiagnosticsService();
            $resp = $encService->deleteDiagnosis($request->post('id', 'NO ICD ID PROVIDED'));

            DB::commit();
            return EhrResponse::jsonSuccess($resp->get('msg'),$resp);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public  function getAssessmentDiagnosisTrail(Request $request){
        try{

            $serv = SoapService::init($request->input('id'), 'NO ENCOUNTER PROVIDED');
            return EhrResponse::jsonResponsePure($serv->getDiagnosisTrail());
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }
}
