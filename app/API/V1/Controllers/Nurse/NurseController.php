<?php


namespace App\API\V1\Controllers\Nurse;

use App\API\V1\Services\EhrResponse;
use App\Exceptions\EhrException\EhrException;
use App\Http\Controllers\Controller;
use App\Models\DeptEncounter;
use App\Models\NurseNotes;
use App\Models\NurseNotesBatch;
use App\Models\NurseWardCatalog;
use App\Services\Nurse\DARNoteService;
use App\Services\Nurse\NurseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

;

class NurseController extends Controller
{
    public function searchAllNursePatients(Request $request)
    {

        $user = auth()->user();
        $data = [
            'personnel_id' => $user->personnel_id,
            'ward_search' => $request->input('ward_search'),
            'person_search' => $request->input('person_search')
        ];

        try {
            $nurse_service = new NurseService();
            $patient_lists = $nurse_service->getPatientLists($data);
            return EhrResponse::jsonResponsePure($patient_lists);
        } catch (EhrException $e) {
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }


    }

    public function getWards()
    {

        $user = auth()->user();
        try {
            $nurse_service = new NurseService($user->personnel);
            $getWards = $nurse_service->getWards();
            return EhrResponse::jsonResponsePure($getWards);
        } catch (EhrException $e) {
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function actionDarNotes(Request $request)
    {
        $user = auth()->user();
        $data = [
            'personnel_id' => $user->personnel_id,
            'encounter_no' => $request->input('encounter_no'),
            'document_id' => $request->input('document_id'),
            'focus' => $request->input('focus'),
            'data' => $request->input('data'),
            'action' => $request->input('action'),
            'response' => $request->input('response'),
        ];
        DB::beginTransaction();
        $nurseService = NurseService::init($data['encounter_no']);
        try {
            $nurseBatchModel = $nurseService->actionNurseBatch($data);
            $result = collect([])->put("data", $nurseBatchModel);
            DB::commit();
            return EhrResponse::jsonSuccess($nurseBatchModel['message'], $result);
        } catch (EhrException $e) {
            $nurseService->resetTracerUpdate();
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            $nurseService->resetTracerUpdate();
            DB::rollBack();
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function deleteDarNote(Request $request)
    {
        $user = auth()->user();
        $data = [
            'personnel_id' => $user->personnel_id,
            'id' => $request->post('id')
        ];

        DB::beginTransaction();
        try {
            $nurseService = new NurseService();
            $deleteDar = $nurseService->deleteDarNote($data);
            $result = collect([])->put("data", $deleteDar);
            DB::commit();
            return EhrResponse::jsonSuccess($deleteDar['message'], $result);
        } catch (EhrException $e) {
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            DB::rollBack();
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function getDarNotes(Request $request){
        $user = auth()->user();
        $data = [
            'encounter_no' => $request->input('encounter_no')
        ];
        $nurseService = NurseService::init($data['encounter_no']);
        $notes = $nurseService->getBatchNote();
        return EhrResponse::jsonResponsePure($notes);
    }

    public function finalizeNote(Request $request){
        $user = auth()->user();
        $data = [
            'encounter_no' => $request->post('encounter_no'),
            'id' => $request->post('id'),
        ];
        try {
            $nurseService = NurseService::init($data['encounter_no']);
            $status = $nurseService->finalizeNote($data);
            $result = collect([])->put("data", $status);
            DB::commit();
            return EhrResponse::jsonSuccess($status['message'], $result);
        } catch (EhrException $e) {
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (Exception $e) {
            DB::rollBack();
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

}