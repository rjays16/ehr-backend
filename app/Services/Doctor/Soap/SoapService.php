<?php

/**
 * Created by PhpStorm.
 * User: debzl
 * Date: 8/26/2019
 * Time: 10:46 PM
 */

namespace App\Services\Doctor\Soap;

use App\Exceptions\EhrException\EhrException;
use App\Exceptions\His\HisActiveResource;
use App\Models\ChiefComplaintCatalog;
use App\Models\Encounter;
use App\Models\EncounterAssessment;
use App\Models\EncounterDxpr;
use App\Models\Mongo\PatientPreAssessment;
use App\Models\Mongo\Tracer\TracerPatientPreassessmentEncounter;
use App\Models\PermissionCatalog;
use App\Models\PersonnelCatalog;
use App\Services\Diagnostic\DiagnosticsService;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\FormActionHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SoapService
{
    /**
     * @var Encounter
     */
    public $encounter;

    /**
     * @var
     */
    public $id;

    /**
     * @var TracerPatientPreassessmentEncounter
     */
    public $tracerAssesssment;

    /**
     * @var PatientPreAssessment
     */
    public $assessment;


    private $_soap_data;

    private $data;

    /** @var PermissionService $permService */
    public $permService;
    public function __construct(Encounter $encounter = null)
    {
        $this->_initCon($encounter);
        $this->permService = new PermissionService($encounter);
    }


    private function _initCon($encounter)
    {
        $this->encounter = $encounter;
        $this->assessment = empty($encounter->latestEncounterAssessment) ? new EncounterAssessment() : $encounter->latestEncounterAssessment;
        $this->tracerAssesssment = $this->assessment->soapAssessment();
    }

    /*
     * @var String $encounter
     * */
    public static function init($encounter){
        $encounter = Encounter::query()->find($encounter);

        if (empty($encounter))
            throw new EhrException('Encounter was not found. ', 404);

        return new SoapService($encounter);
    }






    /** @return bool */
    private function save($type, $data)
    {
        return $this->tracerAssesssment->saveAssessment($this->encounter,[
            $type => $data
        ]);
    }





    public function generateIDfromThisChiefCompl($chiefComplaint_tag)
    {
        $chiefCompCat = new ChiefComplaintCatalog();
        $ids = [];
        $noerror = true;

        foreach ($chiefComplaint_tag as $name) {
            $cat = $chiefCompCat->saveChiefCat($name);
            if (!$cat) {
                $noerror = false;
                break;
            } else {
                $ids[] = $cat;
            }
        }

        return $noerror ? $ids : false;
    }

    /**
     * @return Collection
     */
    public function saveCheifCoplaint($data, $saveToHis = true)
    {
        if(!$this->permService->hasSoapEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $ids = $this->generateIDfromThisChiefCompl($data['chiefComplaint_tag']);
        if (!$ids) {
            $ids = [];
        }


        if(count($ids) <= 0 && trim($data['chiefComplaint_others']) == "")
            throw new EhrException('Nothing to save for chief complaint');

        if (!$this->save('fields', [
            'chief_complaint' => [
                'value' => $ids
            ]
        ])) {
            throw new EhrException('Chief complaint failed.', 500,['chief' => $data]);
        }

        $others_resp = $this->saveSoap_chief_other($data, false);
        if (!$others_resp['saved']) {
            throw new EhrException('Chief complaint failed.', 500,array_merge([['chief' => $data,], $others_resp]));
        }

        $resp=collect();
        if ($saveToHis) {
            //  ===================== HIS trigger event to update there SOAP ================ start
            $his = HisActiveResource::instance();
            $resp = collect($his->onSaveSOAPSubjectiveObjectivePlan($this->encounter->encounter_no, $this->getSoap()));
            //  ===================== HIS trigger event to update there SOAP ================ false
        } else $resp->put('status',true);

        if ($resp->get('status')) {
            return collect(['chief' => $data, 'msg' => 'Chief complaint was updated.'])->merge($others_resp);
        } else
            throw new EhrException('Chief complaint was not updated', 500,[
                'others' => $others_resp,
                'chief' => $data,
                'his_resp' => $his->getResponseData(), // enable once ready to send to HIS
                'resp' => $resp
            ], true);
    }



    public function saveSoap_chief_other($data, $saveToHis = true)
    {
        $modified_info = FormActionHelper::getFormTimeStamp();
        if ($this->save('soap_chief_complaint_other', [
            'value' => $data['chiefComplaint_others']
        ]+$modified_info)) {
            if ($saveToHis) {
                //  ===================== HIS trigger event to update there SOAP ================ start
                $his = HisActiveResource::instance();
                $resp = $his->onSaveSOAPSubjectiveObjectivePlan($this->encounter->encounter_no, $this->getSoap());
                //  ===================== HIS trigger event to update there SOAP ================ false
            } else $resp['status'] = true;

            if ($resp['status']) {
                return ['saved' => true, 'chief' => $data, 'msg' => 'Chief complaint was updated.']+FormActionHelper::getModifier('',$modified_info);
            } else
                throw new EhrException('Other chief complaint is not saved.', 500,['chief' => $data, 'resp' => $resp], true);
        } else
            throw new EhrException('Progress Notes/Clinical Summary was not updated', 500, []);
    }

    public function _allowedAction($require_doctor = true)
    {
        $allowed = auth()->user()->personnel->is_doctor();
        if ($this->encounter->is_discharged)
            throw new EhrException("Already discharged.", 500);
        else if ($allowed != true && $require_doctor)
            throw new EhrException("Must be a doctor.", 500);

        return true;
    }

    /**
     * @return Collection
     */
    public function saveSoapAsseessmentClinicalImp($data, $saveToHis = true, $fromCostcenter = false)
    {
        if(!$this->permService->hasSoapEdit() && !$fromCostcenter)
            throw new EhrException(PermissionService::$errorMessage);

        /*
         * prevent saving if data came form cost center and already has diagnosis
         * */
        $skipSaving = false;
        if ($fromCostcenter && $this->encounter->admit_diagnosis2) {
            $skipSaving = true;
        }

        if (!$skipSaving) {
            $modifier = FormActionHelper::getFormTimeStamp();
            if (!$this->save('soap_assessment_clinical_imp_defined', [
                'value' => $data['impression']
            ]+$modifier)) {
                throw new EhrException('Failed to save clinical impression. (1)', 500);
            }
            if ($this->encounter->currentDeptEncounter->getEncounterTypeHisEquivalent() != "IPD") {

                $this->encounter->admit_diagnosis2 = $data['impression'];
                $this->encounter->modify_id = auth()->user()->personnel->personnel_id;
                $this->encounter->modify_dt = date('Y-m-d h:m:s');

                if ($this->encounter->save()) {
                    $encounterSaved = true;
                } else {
                    throw new EhrException('Clinical Assessment was not updated', 500);
                }
            } else $encounterSaved = true;
        } else {
            $encounterSaved = true;
        }
        $resp = collect();

        if ($saveToHis && $encounterSaved) {
            //  ===================== HIS trigger event to update there SOAP ================ start
            $his = HisActiveResource::instance();
            $resp = collect($his->onSaveSoapClinicalImpression([
                'enc_no' => $this->encounter->encounter_no,
                'from_costcenter' => $fromCostcenter,
                'soap' => $this->getSoap()
            ]));
            //  ===================== HIS trigger event to update there SOAP ================ false
        } else $resp->put('status', true);

        if ($resp->get('status')) {
            return collect([
                'from_costcenter' => $fromCostcenter,
                'msg' => 'Clinical assessment was updated',
            ])->merge(FormActionHelper::getModifier('',$modifier));
        } else {
            throw new EhrException('Failed to save clinical assessment. ' . isset($resp['msg']) ? $resp['msg'] : '', 500,
                ['resp' => $resp,
                'data_' => $data,
                'from_costcenter' => $fromCostcenter], true);
        }
    }



    /**
     * @return Collection
     */
    public function saveAssessment_finalDiag($data, $saveToHis = true)
    {
        if(!($this->permService->hasSoapDiagEdit() || $this->permService->hasSoapEdit()))
            throw new EhrException(PermissionService::$errorMessage);

        $modifier = FormActionHelper::getFormTimeStamp();
        if ($this->save('soap_diag_final', [
            'value'       => $data['text-assessment-final']
        ]+$modifier)) {

            if ($saveToHis) {
                //  ===================== HIS trigger event to update there SOAP ================ start
                $his = HisActiveResource::instance();
                $resp = $his->onSaveSOAPDiagnosisupdated($this->encounter->encounter_no, $this->getSoap());
                //  ===================== HIS trigger event to update there SOAP ================ false
            } else $resp['status'] = true;

            if ($resp['status']) {
                return collect([
                    'code'  => 200,
                    'saved' => true,
                    'msg'   => 'Diagnosis was updated',
                ])->merge(FormActionHelper::getModifier('',$modifier));
            } else
                throw new EhrException($resp['msg'], 500,[ 'data' => $resp,'type' => 'final', 'resp_data' => $his->getResponseData()], true);
        } else
            throw new EhrException('Diagnosis was not updated', 500, ['type' => 'final']);
    }


    /**
     * @return Collection
     */
    public function saveAssessment_OtherDiag($data, $saveToHis = true)
    {
        $modifier = FormActionHelper::getFormTimeStamp();
        if ($this->save('soap_diag_other', [
            'value'       => $data['text-assessment-other'] == "null" ? '' : $data['text-assessment-other']
        ]+$modifier)) {

            if ($saveToHis) {
                //  ===================== HIS trigger event to update there SOAP ================ start
                $his = HisActiveResource::instance();
                $resp = $his->onSaveSOAPDiagnosisupdated($this->encounter->encounter_no, $this->getSoap());
                //  ===================== HIS trigger event to update there SOAP ================ false
            } else $resp['status'] = true;

            if ($resp['status']) {
                return collect([
                    'code'  => 200,
                    'saved' => true,
                    'msg'   => 'Diagnosis was updated',
                ])->merge(FormActionHelper::getModifier('',$modifier));
            } else
                throw new EhrException($resp['msg'], 500,[ 'data' => $resp,'type' => 'final', 'resp_data' => $his->getResponseData()], true);
        } else
            throw new EhrException('Diagnosis was not updated', 500, ['type' => 'other']);
    }


    /**
     * @return Collection
     */
    public function savePlan($data, $saveToHis = true)
    {
        if(!$this->permService->hasSoapEdit())
            throw new EhrException(PermissionService::$errorMessage);

        $modifier = FormActionHelper::getFormTimeStamp();
        if ($this->save('soap_plan', [
            'value' => $data['text-assessment-plan'] == "null"? '' : $data['text-assessment-plan']
        ]+$modifier)) {

            if ($saveToHis) {
                //  ===================== HIS trigger event to update there SOAP ================ start
                $his = HisActiveResource::instance();
                $resp = $his->onSaveSOAPSubjectiveObjectivePlan($this->encounter->encounter_no, $this->getSoap());
                //  ===================== HIS trigger event to update there SOAP ================ false
            } else $resp['status'] = true;

            if ($resp['status']) {
                return collect([
                    'saved' => true,
                    'code' => 200,
                    'msg' => 'Progress Notes/Clinical Summary was updated',
                ])->merge(FormActionHelper::getModifier('',$modifier));
            } else
                throw new EhrException('Progress Notes/Clinical Summary was not updated', 500,['his_rep' => $his->getResponseData()] , true);
        } else
            throw new EhrException('Progress Notes/Clinical Summary was not updated', 500, []);
    }

    /**
     * @return Collection
     */
    public function saveObjective($data, $saveToHis = true)
    {
        if(!$this->permService->hasSoapEdit())
            throw new EhrException(PermissionService::$errorMessage);

        $modifier = FormActionHelper::getFormTimeStamp();
        if ($this->save('soap_objective', [
            'value' => $data['text-objective'] == "null" ? '' :$data['text-objective']
        ]+$modifier)) {

            if ($saveToHis) {
                //  ===================== HIS trigger event to update there SOAP ================ start
                $his = HisActiveResource::instance();
                $resp = $his->onSaveSOAPSubjectiveObjectivePlan($this->encounter->encounter_no, $this->getSoap());
                //  ===================== HIS trigger event to update there SOAP ================ false
            } else $resp['status'] = true;

            if ($resp['status']) {
                return collect([
                    'code' => 200,
                    'saved' => true,
                    'msg' => 'Pertinent Physical Examination was updated',
                ]+FormActionHelper::getModifier('',$modifier));
            } else
                throw new EhrException('Pertinent Physical Examination was not updated', 500, ['his_resp' => $his->getResponseData()], true);
        } else
            throw new EhrException('Pertinent Physical Examination was not updated', 500, []);
    }

    public function getAdmittingDiag($id)
    {
        return $this->encounter->admit_diagnosis2;
    }


    public function getClinicalImpression($id)
    {
        $ad = $this->getAdmittingDiag($id);
        $clinical = $this->getClinicalImpDefined();
        return [
            'admitting_diag' => $ad,
            'icds' => $this->getAllICD($id),
            'icdEncoded' => DiagnosticsService::getEncoder($id),
            'clinical_imp' => $clinical ? $clinical : $ad
        ]+FormActionHelper::getModifier('admitting_diag_', $this->getClinicalImpDefinedData());
    }





    public function getClinicalImpDefined()
    {
        return $this->getClinicalImpDefinedData()['value'];
    }

    public function getClinicalImpDefinedData()
    {
        $data = $this->getData();
        return $data['soap_assessment_clinical_imp_defined'];
    }


    public function getAllICD($id)
    {
        $model = EncounterDxpr::query()
                    ->where('encounter_no', $id)
                    // ->where('is_deleted', 0)
                    ->get();

        $res = $model->map(function($icd) {
            $icd['modified_by'] = $icd['modify_id'];
            $icd['modified_dt'] = $icd['modify_dt'];
            $icd->modified_by_fullname = FormActionHelper::getModifier('',$icd->toArray())['modified_by_fullname'];
            return $icd;
        });

        return $res;
    }


    public function getCheifComplaintSelected($encounterNo=null)
    {
        $this->id = Str::uuid()->toString();
        $model = new ChiefComplaintCatalog();
        $ids = $this->getSoapSubjective();
        $others = $this->getSoapSubjectiveOthers();
        $data = $model->getAllOptionsSeletecText($ids);
        return [
            'id' => $this->id,
            'ids' => $data,
            'names' => implode('; ', $data),
            'others' => $others,
        ]+FormActionHelper::getModifier('',$others);
    }

    public function getCheifComplaintCatalog()
    {
        $model = new ChiefComplaintCatalog();
        $data = $model->getAllOptionsActiveText();
        return $data;
    }

    public function getSoapSubjective()
    {
        $obj = $this->getData();
        return $obj['fields']['chief_complaint']['value'];
    }

    public function getSoapSubjectiveOthers()
    {
        $obj = $this->getData();
        return $obj['soap_chief_complaint_other'];
    }



    public function getSoap()
    {
        $this->_soap_data = [
            'subjective' => $this->getCheifComplaintSelected($this->encounter->encounter_no),
            'objective' => $this->getSoapObjective(),
            'clinical_imp' => $this->getClinicalImpression($this->encounter->encounter_no),
            'final_diag' => $this->getSoapFinalDiag(),
            'other_diag' => $this->getSoapOther(),
            'plan' => $this->getSoapPlan(),
        ];
        return $this->_soap_data;
    }

    public function getSoapObjective()
    {
        $obj = $this->getData();
        return is_null($obj['soap_objective']) ?null: collect(array_merge($obj['soap_objective'], ['id' => $this->id]))->merge(FormActionHelper::getModifier('',$obj['soap_objective']))->toArray();
    }

    public function getData($include_id = true)
    {
        return $this->tracerAssesssment->getTracerModel();
    }

    public function getSoapFinalDiag()
    {
        $obj = $this->getData();
        return is_null($obj['soap_diag_final']) ?null: collect(array_merge($obj['soap_diag_final'], ['id' => $this->id]))->merge(FormActionHelper::getModifier('',$obj['soap_diag_final']))->toArray();
    }

    public function getSoapOther()
    {
        $obj = $this->getData();
        return is_null($obj['soap_diag_other']) ?null: collect(array_merge($obj['soap_diag_other'], ['id' => $this->id]))->merge(FormActionHelper::getModifier('',$obj['soap_diag_other']))->toArray();
    }

    public function getSoapPlan()
    {
        $obj = $this->getData();
        return is_null($obj['soap_plan']) ?null: collect(array_merge($obj['soap_plan'], ['id' => $this->id]))->merge(FormActionHelper::getModifier('',$obj['soap_plan']))->toArray();
    }


    public function getDiagnosisTrail()
    {
        $his = HisActiveResource::instance();
        $data = $his->getSoapDiagAuditTrail($this->encounter->encounter_no);
        if(!$data)
            throw new EhrException($data['msg'], 500, array_merge($data, ['his_resp'=>$his->getResponseData()]));

        return $data['data'];
    }


    public static function subjectiveOptions()
    {
        return (new SoapService(new Encounter()))->getCheifComplaintCatalog();
    }


    public static function config()
    {
        /**
         * @var \Illuminate\Database\Eloquent\Collection $other_permissionList
         */

        $other_permissionList = PermissionService::getAllPermissions([
            // list all permission id needed from here.
            113,
        ]);

        return [
            'm-patient-soap-subjective' => [
                'subjective-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'subjective-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
                'default-options' => [
                    'subjective' => [
                        // 'chiefcomp' => (new SoapService(new Encounter()))->getCheifComplaintCatalog()
                        'chiefcomp' => []
                    ]
                ]
            ],
            'm-patient-soap-objective' => [
                'objective-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'objective-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ]
            ],
            'm-patient-soap-assessment' => [
                'impression-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'impression-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => [

                    ]
                ],
                'icd-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'icd-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
                'icd-remove' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
                'diagnsosis-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'diagnsosis-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => [
                        $other_permissionList->where('id', 113)->first()->toArray(), // _a_1_doctorseditdiagnosis
                    ]
                ],
                'diagnsosis-trail-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ]
            ],
            'm-patient-soap-plan' => [
                'plan-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'plan-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ]
            ],
        ];
    }
}
