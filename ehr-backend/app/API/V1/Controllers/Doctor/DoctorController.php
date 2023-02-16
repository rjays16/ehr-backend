<?php


namespace App\API\V1\Controllers\Doctor;

use App\API\V1\Services\EhrResponse;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Services\Doctor\DoctorService;
use App\Services\Doctor\WebService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Doctor\CF4Service;
use Exception;

class DoctorController extends Controller
{
    public function searchAllDoctorPatients(Request $request){
        $user = auth()->user();
        $data = [
            'personnel_id'   => $user->personnel_id,
            'patient_type'   => $request->input('patient_type'),
            'isBarcode'   => $request->input('isBarcode',0), 
            'person_search'   => $request->input('person_search'),
            'encounter_start_date'   => $request->input('encounter_start_date'),
            'encounter_end_date'   => $request->input('encounter_end_date'),
            'limit'   => $request->input('limit'),
        ];
        

        try {
            $doctor_service = new DoctorService($user->personnel);
            $patient_lists = $doctor_service->getPatientLists($data);
            return EhrResponse::jsonResponsePure($patient_lists);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(),[], $e->getTrace());
        }


    }


    public function webserviceSearchAllPatients(Request $request){
        $user = auth()->user();
        $data = [
            'patient_type'   => $request->input('patient_type'),
            'person_search'   => $request->input('person_search'),
            'start_date'   => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        try {
            $web_service = new WebService();
            $patient_lists = $web_service->getPatientListsWebservice($data);
            return EhrResponse::jsonResponsePure($patient_lists);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(),[], $e->getTrace());
        }


    }


    public function allDoctorTaggedPatients(Request $request){
        

        try {
            $doctor_service = new DoctorService($request->user()->personnel);
            return EhrResponse::jsonResponsePure($doctor_service->getTaggedPatients());
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }

    }


    public function tagPatient(Request $request){
        

        try {
            $doctor_service = new DoctorService($request->user()->personnel);
            return EhrResponse::jsonSuccess($doctor_service->favoritePatient($request->post('encounter_no')));
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(),$e->getCode(), $e->getRespDataJson());
        } catch (Exception $e) {
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(),[], $e->getTrace());
        }

    }

    public function checkIncompleData(Request $request){
        $service = CF4Service::init($request->input('encounter_no'));
        $service->checkMandatoryFields();
    }
}