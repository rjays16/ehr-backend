<?php


namespace App\API\V1\Controllers\Doctor;

use App\API\V1\Services\EhrResponse;
use App\Models\Encounter;
use App\Exceptions\EhrException\EhrException;
use App\Exceptions\EhrException\EhrLogException;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\Doctor\PMH\ParagraphFormService;
use App\Services\Patient\PatientService;
use App\Services\Person\PersonService;
use App\Services\Doctor\PMH\PastMedicalHistoryService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;

class PastMedicalController extends Controller
{

    public function patientPresentIllness(Request $request){
        try {
            $patient_service = PastMedicalHistoryService::init($request->input('encounter_no'));
            $presentIllness = $patient_service->generatePresentIllness();
            return EhrResponse::jsonResponsePure($presentIllness);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function getOptions(Request $request){
        try {
            return EhrResponse::jsonResponsePure(PastMedicalHistoryService::getOptions());
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function patientPastMedicalHistory(Request $request){
        try {
            $patient_service = PastMedicalHistoryService::init($request->input('encounter_no'));
            $pastMedicalHistory = $patient_service->generatePastMedicalHistory();
            return EhrResponse::jsonResponsePure($pastMedicalHistory);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function patientSurgicalHistory(Request $request){
        try {
            $patient_service = PastMedicalHistoryService::init($request->input('encounter_no'));
            $pastSurgicalHistory = $patient_service->generateSurgicalHistory();
            return EhrResponse::jsonResponsePure($pastSurgicalHistory);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function patientFamilyHistory(Request $request){
        try {
            $patient_service = PastMedicalHistoryService::init($request->input('encounter_no'));
            $pastMedicalHistory = $patient_service->generateFamilyHistory();
            return EhrResponse::jsonResponsePure($pastMedicalHistory);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function patientSocialHistory(Request $request){
        try {
            $patient_service = PastMedicalHistoryService::init($request->input('encounter_no'));
            $socialHistory = $patient_service->generateSocialHistory();
            return EhrResponse::jsonResponsePure($socialHistory);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function patientImmunizationRecord(Request $request){
        try {
            $patient_service = PastMedicalHistoryService::init($request->input('encounter_no'));
            $immunizationRecord = $patient_service->generateImmunizationRecord();
            return EhrResponse::jsonResponsePure($immunizationRecord);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function patientGynecologicalObstetricHistory(Request $request){
        try {
            $patient_service = PastMedicalHistoryService::init($request->input('encounter_no'));
            $pregnantHistory = $patient_service->generatePregnantHistory();
            $menstrualHistory = $patient_service->generateMenstrualHistory();
            $result['data'] = [
                "menstrualHistory" =>  $menstrualHistory,
                "pregnantHistory"   => $pregnantHistory
            ];
            return EhrResponse::jsonResponsePure($result);
        }catch (EhrException $e){
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (\Exception $e){
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function actionPresentIllness(Request $request){
        DB::beginTransaction();
        $data = [
            "encounter_no"  =>  $request->post('encounter_no'),
            "history"  =>  $request->post('history'),
            "modified_by"  =>  $request->post('modified_by'),
            "updated_at"  =>  $request->post('updated_at'),
        ];
        try {
            $pmh_service = PastMedicalHistoryService::init($data['encounter_no']);
            $presentIllness = $pmh_service->actionPresentIllness($data);
            $result = collect([])->put("data", $presentIllness);
            DB::commit();
            return EhrResponse::jsonSuccess($presentIllness['message'], $result);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function actionPastMedicalHistory(Request $request){
        DB::beginTransaction();
        $data = [
            "encounter_no"  =>  $request->post('encounter_no'),
            "disease_id"  =>  $request->post('disease_id'),
            "specific_disease_description"  =>  $request->post('specific_disease_description')
        ];
        try {
            $pmh_service = PastMedicalHistoryService::init($data['encounter_no']);
            $pastMedicalHistory = $pmh_service->actionPastMedicalHistory($data);
            $paragraphService = ParagraphFormService::init($data['encounter_no']);
            $paragraphService->paragraphForm();
            $result = collect([])->put("data", $pastMedicalHistory);
            DB::commit();
            return EhrResponse::jsonSuccess($pastMedicalHistory['message'], $result);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (Exception $e){
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function deletePastMedicalHistory(Request $request){
        DB::beginTransaction();
        $data = [
            "encounter_no"  =>  $request->post('encounter_no'),
            "id"  =>  $request->post('id')
        ];
        try {
            $pmh_service = PastMedicalHistoryService::init($data['encounter_no']);
            $pastMedicalHistory = $pmh_service->deletePastMedicalHistory($data);
            $paragraphService = ParagraphFormService::init($data['encounter_no']);
            $paragraphService->paragraphForm();
            DB::commit();
            return EhrResponse::jsonSuccess($pastMedicalHistory['message'], $pastMedicalHistory);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (\Exception $e) {
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function actionSurgicalHistory(Request $request){
        DB::beginTransaction();
        $data = [
            "encounter_no"  =>  $request->post('encounter_no'),
            "description"  =>  $request->post('description'),
            "date_of_operation"  =>  $request->post('date_of_operation'),
            "remarks"  =>  $request->post('remarks')
        ];
        try {
            $pmh_service = PastMedicalHistoryService::init($data['encounter_no']);
            $surgicalHistory = $pmh_service->addSurgicalHistory($data);
            $paragraphService = ParagraphFormService::init($data['encounter_no']);
            $paragraphService->paragraphForm();
            $result = collect([])->put("data", $surgicalHistory);
            DB::commit();
            return EhrResponse::jsonSuccess($surgicalHistory['message'], $result);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (\Exception $e) {
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function deleteSurgicalHistory(Request $request){
        DB::beginTransaction();
        $data = [
            "encounter_no"  =>  $request->post('encounter_no'),
            "id"  =>  $request->post('id'),
        ];
        try {
            $pmh_service = PastMedicalHistoryService::init($data['encounter_no']);
            $surgicalHistory = $pmh_service->deleteSurgicalHistory($data);
            $paragraphService = ParagraphFormService::init($data['encounter_no']);
            $paragraphService->paragraphForm();
            DB::commit();
            return EhrResponse::jsonSuccess($surgicalHistory['message'], $surgicalHistory);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (\Exception $e) {
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function actionFamilyHistory(Request $request){
        DB::beginTransaction();
        $data = [
            "encounter_no"  =>  $request->post('encounter_no'),
            "disease_id"  =>  $request->post('disease_id'),
            "specific_disease_description"  =>  $request->post('specific_disease_description'),
        ];
        try {
            $pmh_service = PastMedicalHistoryService::init($data['encounter_no']);
            $familyHistory = $pmh_service->actionFamilyHistory($data);
            $paragraphService = ParagraphFormService::init($data['encounter_no']);
            $paragraphService->paragraphForm();
            $result = collect([])->put("data", $familyHistory);
            DB::commit();
            return EhrResponse::jsonSuccess($familyHistory['message'], $result);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (\Exception $e) {
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function deleteFamilyHistory(Request $request){
        DB::beginTransaction();
        $data = [
            "encounter_no"  =>  $request->post('encounter_no'),
            "id"  =>  $request->post('id')
        ];
        try {
            $pmh_service = PastMedicalHistoryService::init($data['encounter_no']);
            $familyHistory = $pmh_service->deleteFamilyHistory($data);
            $paragraphService = ParagraphFormService::init($data['encounter_no']);
            $paragraphService->paragraphForm();
            DB::commit();
            return EhrResponse::jsonSuccess($familyHistory['message'], $familyHistory);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (\Exception $e) {
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function actionSocialHistory(Request $request){
        DB::beginTransaction();
        $data = [
            "encounter_no"  =>  $request->post('encounter_no'),
            "spin"  =>  $request->post('spin'),
            "is_smoke"  =>  $request->post('is_smoke'),
            "years_smoking"  =>  $request->post('years_smoking'),
            "stick_per_day"  =>  $request->post('stick_per_day'),
            "stick_per_year"  =>  $request->post('stick_per_year'),
            "is_alcohol"  =>  $request->post('is_alcohol'),
            "is_drug"  =>  $request->post('is_drug'),
            "no_bottles"  =>  $request->post('no_bottles'),
            "remarks"  =>  $request->post('remarks'),
        ];
        try {
            $pmh_service = PastMedicalHistoryService::init($data['encounter_no']);
            $socialHistory = $pmh_service->actionSocialHistory($data);
            $paragraphService = ParagraphFormService::init($data['encounter_no']);
            $paragraphService->paragraphForm();
            $result = collect([])->put("data", $socialHistory);
            DB::commit();
            return EhrResponse::jsonSuccess($socialHistory['message'], $result);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (\Exception $e) {
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function actionMenstrualHistory(Request $request){
        DB::beginTransaction();
        $data = [
            "encounter_no"  =>  $request->post('encounter_no'),
            "is_applicable_menstrual"  =>  $request->post('is_applicable_menstrual'),
            "age_first_menstrual"  =>  $request->post('age_first_menstrual'),
            "last_period_menstrual"  =>  $request->post('last_period_menstrual'),
            "no_days_menstrual_period"  =>  $request->post('no_days_menstrual_period'),
            "interval_menstrual_period"  =>  $request->post('interval_menstrual_period'),
            "no_pads"  =>  $request->post('no_pads'),
            "age_sex_intercourse"  =>  $request->post('age_sex_intercourse'),
            "birth_control_used"  =>  $request->post('birth_control_used'),
            "is_menopause"  =>  $request->post('is_menopause'),
            "age_menopause"  =>   $request->post('age_menopause'),
            "remarks"  =>  $request->post('remarks') ? $request->post('remarks') : "",
        ];
        try {
            $pmh_service = PastMedicalHistoryService::init($data['encounter_no']);
            $menstruaHistory = $pmh_service->actionMenstrualHistory($data);
            $paragraphService = ParagraphFormService::init($data['encounter_no']);
            $paragraphService->paragraphForm();
            $result = collect([])->put("data", $menstruaHistory);
            DB::commit();
            return EhrResponse::jsonSuccess($menstruaHistory['message'], $result);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        }catch (\Exception $e){
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function actionPregnantHistory(Request $request){
        $data = [
            "encounter_no"  =>  $request->post('encounter_no'),
            "is_applicable_pregnant"  =>  $request->post('is_applicable_pregnant'),
            "date_gravidity"  =>  $request->post('date_gravidity'),
            "date_parity"  =>  $request->post('date_parity'),
            "type_delivery"  =>  $request->post('type_delivery') ? $request->post('type_delivery') : "",
            "no_full_term_preg"  =>  $request->post('no_full_term_preg'),
            "no_premature"  =>  $request->post('no_premature'),
            "no_abortion"  =>  $request->post('no_abortion'),
            "no_living_children"  =>  $request->post('no_living_children'),
            "induced_hyper"  =>  $request->post('induced_hyper') ? $request->post('induced_hyper') : "",
            "family_planning"  =>  $request->post('family_planning') ? $request->post('family_planning') : "",
            "remarks"  =>  $request->post('remarks') ? $request->post('remarks') : "",
        ];
        DB::beginTransaction();
        try {
            $pmh_service = PastMedicalHistoryService::init($data['encounter_no']);
            $pregnantHistory = $pmh_service->actionPregnantHistory($data);
            $paragraphService = ParagraphFormService::init($data['encounter_no']);
            $paragraphService->paragraphForm();
            $result = collect([])->put("data", $pregnantHistory);
            DB::commit();
            return EhrResponse::jsonSuccess($pregnantHistory['message'], $result);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (\Exception $e) {
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }

    public function actionImmunizationRecord(Request $request){
        DB::beginTransaction();
        $data = [
            "encounter_no"  =>  $request->post('encounter_no'),
            "child_id"  =>  $request->post('child_id'),
            "young_id"  =>  $request->post('young_id'),
            "preg_id"  =>  $request->post('preg_id'),
            "elder_id"  =>  $request->post('elder_id'),
            "other_code"  =>  $request->post('other_code'),
            "remarks"  =>  $request->post('remarks')
        ];
        try {
            $pmh_service = PastMedicalHistoryService::init($data['encounter_no']);
            $immuRecord = $pmh_service->actionImmunizationRecord($data);
            $paragraphService = ParagraphFormService::init($data['encounter_no']);
            $paragraphService->paragraphForm();
            $result = collect([])->put("data", $immuRecord);
            DB::commit();
            return EhrResponse::jsonSuccess($immuRecord['message'], $result);
        }catch (EhrException $e){
            DB::rollBack();
            return EhrResponse::jsonResponse($e->getMessage(), $e->getCode(), $e->getRespDataJson(), $e->getTrace());
        } catch (\Exception $e) {
            DB::rollBack();
            new EhrLogException($e, $request->all());
            return EhrResponse::jsonError500($e->getMessage(), [], $e->getTrace());
        }
    }


}