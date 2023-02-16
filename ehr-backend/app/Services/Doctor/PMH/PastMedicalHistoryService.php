<?php


namespace App\Services\Doctor\PMH;

use App\Exceptions\EhrException\EhrException;
use App\Models\Encounter;
use App\Models\FamilyHistory;
use App\Models\ImmunizationRecord;
use App\Models\MenstrualHistory;
use App\Models\PastMedicalHistory;
use App\Models\PersonnelCatalog;
use App\Models\PhilImmchild;
use App\Models\PhilImmelderly;
use App\Models\PhilImmpregw;
use App\Models\PhilImmyoungw;
use App\Models\PregnantHistory;
use App\Models\PresentIllness;
use App\Models\SocialHistory;
use App\Models\SurgicalHistory;
use App\Models\PhilDisease;
use App\Services\Doctor\PMH\ParagraphFormService;
use Illuminate\Support\Facades\Auth;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\FormActionHelper;
use Illuminate\Validation\Rule;


class PastMedicalHistoryService
{

    /**
     * @var Encounter
     */
    private $_encounter;
    private $_spin;
    const IS_NOT_DELETED = 0;
    const IS_DELETED = 1;

    /** @var PermissionService $permService */
    public $permService;
    function __construct(Encounter $encounter=null)
    {
        $this->_encounter = $encounter;
        $this->_spin =  $encounter->spin;
        $this->permService = new PermissionService($encounter);
    }

    /**
     * @return PastMedicalHistoryService
    */
    public static function init($encounter){
        $encounter = Encounter::query()->find($encounter);
        if (is_null($encounter))
            throw new EhrException('Encounter was not found. ');
        /**
         * @var Encounter $encounter
         */
        return new PastMedicalHistoryService($encounter);
    }

    /**
     * @return array
     */
    public function generatePresentIllness(){
        $data = $this->modifiedName($this->_encounter->presentIllnes, false);
        if($data)
            $data['modified_name'] = FormActionHelper::getModifier('',[
                'modified_dt' => $data['updated_at'] ? $data['updated_at'] : $data['created_at'],
                'modified_by' => $data['modified_by'] ? $data['modified_by'] : $data['created_by'],
            ])['modified_by'];
            $data['modified_by_fullname'] = FormActionHelper::getModifier('',[
                'modified_dt' => $data['updated_at'] ? $data['updated_at'] : $data['created_at'],
                'modified_by' => $data['modified_by'] ? $data['modified_by'] : $data['created_by'],
            ])['modified_by_fullname'];

        return $data;
    }

    public function generatePastMedicalHistory(){
        return $this->modifiedName($this->_encounter->pastMedicalHistory);
    }

    public function generateSurgicalHistory(){
        return $this->modifiedName($this->_encounter->surgicalHistory);
    }

    public function generateFamilyHistory(){
        return $this->modifiedName($this->_encounter->familyHistory);
    }

    public function generateSocialHistory(){
        $data =  $this->modifiedName($this->_encounter->socialHistory, false);
        if($data)
            $data['modified_name'] = FormActionHelper::getModifier('',[
                'modified_dt' => $data['modified_at'] ? $data['modified_at'] : $data['created_at'],
                'modified_by' => $data['modified_by'] ? $data['modified_by'] : $data['created_by'],
            ])['modified_by'];

        return $data;
    }

    public function generateImmunizationRecord(){
        $data = $this->modifiedName($this->_encounter->immunizationRecord, false);
        if($data)
            $data['modified_name'] = FormActionHelper::getModifier('',[
                'modified_dt' => $data['updated_at'] ? $data['updated_at'] : $data['created_at'],
                'modified_by' => $data['updated_by'] ? $data['updated_by'] : $data['created_by'],
            ])['modified_by'];

        return $data;
    }

    public function generatePregnantHistory(){
        return $this->modifiedName($this->_encounter->pregnantHistory, false);
    }

    public function generateMenstrualHistory(){
        $data =  $this->modifiedName($this->_encounter->menstrualHistory, false);
        if($data)
            $data['modified_name'] = FormActionHelper::getModifier('',[
                'modified_dt' => $data['modified_at'] ? $data['modified_at'] : $data['created_at'],
                'modified_by' => $data['modified_by'] ? $data['modified_by'] : $data['created_by'],
            ])['modified_by'];

        return $data;
    }

    public static function getDiseases(){
        $model = new PhilDisease();
        $data = $model->getPhilDiseases();
        return $data;
    }

    public static function getImmChildData(){
        $model = new PhilImmchild();
        $data = $model->getImmChildData();
        return $data;
    }

    public static function getImmYoungData(){
        $model = new PhilImmyoungw();
        $data = $model->getImmYoungData();
        return $data;
    }

    public static function getImmElderlyData(){
        $model = new PhilImmelderly();
        $data = $model->getImmElderlyData();
        return $data;
    }

    public function getImmPregwData(){
        $model = new PhilImmpregw();
        $data = $model->getImmPregwData();
        return $data;
    }

    private function _getDate($data_array)
    {
        $date = null;
        if(isset($data_array['created_at'])){
            if(!(is_null($data_array['created_at']) && empty($data_array['created_at'])))
                return $data_array['created_at'];
        }

        if(isset($data_array['updated_at'])){
            if(!(is_null($data_array['updated_at']) && empty($data_array['updated_at'])))
                return $data_array['updated_at'];
        }

        return $data_array['updated_at'];
    }

    public function modifiedName($datas, $get=true){
        if(!empty($datas)){
            $datas = $datas->toArray();
            if($get){
                foreach ($datas as $key => $info){
                    $date = $this->_getDate($info);
                    $info['modified_name'] = FormActionHelper::getModifier('',[
                        'modified_dt' => $date,
                        'modified_by' => $info['personnel']['personnel_id']
                    ])['modified_by'];
                    $info['modified_by_fullname'] = FormActionHelper::getModifier('',[
                        'modified_dt' => $date,
                        'modified_by' => $info['personnel']['personnel_id']
                    ])['modified_by_fullname'];

                    $datas[$key] = $info;
                }
            }else{
                $date = $this->_getDate($datas);
                $datas['modified_name'] = FormActionHelper::getModifier('',[
                    'modified_dt' => $date,
                    'modified_by' => $datas['personnel']['personnel_id']
                ])['modified_by'];
                $datas['modified_by_fullname'] = FormActionHelper::getModifier('',[
                    'modified_dt' => $date,
                    'modified_by' => $datas['personnel']['personnel_id']
                ])['modified_by_fullname'];
            }
        }
        return $datas;
    }

    public function actionPresentIllness($data){

        if(!$this->permService->hasPastMedEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $rules = [
            'history' => 'required|max:2000',
        ];

        $validator = validator($data, $rules);

        if($validator->fails())
            throw new EhrException("Invalid Fields!",500, [
                'errors' => $validator->errors()
            ]);
        $modifier = FormActionHelper::getFormTimeStamp();

        $model = $this->_encounter->presentIllnes;
        if(empty($model)){
            $model = new PresentIllness();
            $model->encounter_no = $this->_encounter->encounter_no;
            $model->spin = $this->_spin;
            $model->created_by = $modifier['modified_by'];
            $model->created_at = $modifier['modified_dt'];
        }
        $model->history = $data['history'];
        $model->modified_by = $modifier['modified_by'];
        $model->updated_at = $modifier['modified_dt'];
        if(!$model->save())
            throw new EhrException('Unable to save present illness ');

        $modifier = FormActionHelper::getModifier('', $modifier);
        return [
            'message'   => "Present Illness Successfully saved!",
            'data'      =>  $model,
            'modified_by'   => $modifier['modified_by'],
            'updated_at' => $modifier['modified_dt'],
        ];
    }

    public function actionPastMedicalHistory($data){


        if(!$this->permService->hasPastMedEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $rules = [
            'specific_disease_description' => 'required|max:2000',
        ];

        $validator = validator($data, $rules);

        if($validator->fails())
            throw new EhrException("Invalid Fields!",500, [
                'errors' => $validator->errors()
            ]);

        $modifier = FormActionHelper::getFormTimeStamp();

        $model = new PastMedicalHistory();
        $has_noneDisease = $model->checkPastMedicalHistory($this->_encounter->encounter_no);

        if($data['disease_id']==1){
            if($has_noneDisease){
                throw new EhrException('Cannot select none again!');
            }
        }
        $model->encounter_no = $this->_encounter->encounter_no;
        $model->spin = $this->_spin;
        $model->disease_id = $data['disease_id'];
        $model->specific_disease_description = $data['specific_disease_description'];
        $model->is_specific = $data['specific_disease_description'] != '' ? 1 : 0;
        $model->is_deleted = self::IS_NOT_DELETED;
        $model->created_by = $modifier['modified_by'];
        $model->deleted_by = "";
        $model->deleted_at = $modifier['modified_dt'];

        if(!$model->save())
            throw new EhrException('Unable to save Past Medical History ');

        $modifier = FormActionHelper::getModifier('', $modifier);
        return [
            'message'   => "Past Medical History successfully added!",
            'data'      =>  $model,
            'modified_by'   => $modifier['modified_by'],
            'updated_at'    => $modifier['modified_dt']
        ];
    }

    public function deletePastMedicalHistory($data){
        if(!$this->permService->hasPastMedEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $rules = [
            'id' => 'required',
        ];

        $validator = validator($data, $rules);

        if($validator->fails())
            throw new EhrException("Invalid Fields!",500, [
                'errors' => $validator->errors()
            ]);

        $model = PastMedicalHistory::query()->where([
            ["encounter_no", $this->_encounter->encounter_no],
            ["id", $data['id']]
        ])->first();

        $model->is_deleted = self::IS_DELETED;
        $model->deleted_at = date('Y-m-d h:m:s');
        $model->deleted_by = Auth::user()->personnel_id;

        if(!$model->save())
            throw new EhrException('Unable to delete Past Medical History ');

        return [
            'message'   => "Past Medical History successfully deleted!",
            'data'      =>  $data
        ];
    }

    public function addSurgicalHistory($data){
        if(!$this->permService->hasPastMedEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $rules = [
            'date_of_operation' => ['required', 'date_format:Y-m-d'],
            'description' => 'max:2000',
            'remarks' => 'max:2000'
        ];

        $validator = validator($data, $rules);

        if($validator->fails())
            throw new EhrException("Invalid Fields!",500, [
                'errors' => $validator->errors()
            ]);

        $modifier = FormActionHelper::getFormTimeStamp();

        $model = new SurgicalHistory();
        $model->encounter_no = $this->_encounter->encounter_no;
        $model->spin = $this->_spin;
        $model->description = $data['description'];
        $model->date_of_operation = $data['date_of_operation'];
        $model->remarks = $data['remarks'];
        $model->is_deleted = self::IS_NOT_DELETED;
        $model->created_by = $modifier['modified_by'];
        $model->deleted_by = "";
        $model->deleted_at = $modifier['modified_dt'];

        if(!$model->save())
            throw new EhrException('Unable to delete Past Medical History ');

        $modifier = FormActionHelper::getModifier('', $modifier);

        return [
            'message'   => "Surgical History successfully added!",
            'data'      =>  $model,
            'modified_by'   => $modifier['modified_by'],
            'updated_at' => $modifier['modified_dt']
        ];
    }


    public function deleteSurgicalHistory($data){

        if(!$this->permService->hasPastMedEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $rules = [
            'id' => 'required',
        ];

        $validator = validator($data, $rules);

        if($validator->fails())
            throw new EhrException("Invalid Fields!",500, [
                'errors' => $validator->errors()
            ]);

        $model = SurgicalHistory::query()->where([
            ["encounter_no", $this->_encounter->encounter_no],
            ["id", $data['id']]
        ])->first();

        $model->is_deleted = self::IS_DELETED;
        $model->deleted_at = date('Y-m-d h:m:s');
        $model->deleted_by = Auth::user()->personnel_id;

        if(!$model->save())
            throw new EhrException('Unable to delete Surgical History ');

        return [
            'message'   => "Past Medical History successfully deleted!",
            'data'      =>  $data
        ];
    }

    public function actionFamilyHistory($data){
        if(!$this->permService->hasPastMedEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $rules = [
            'specific_disease_description' => 'required|max:2000',
        ];

        $validator = validator($data, $rules);

        if($validator->fails())
            throw new EhrException("Invalid Fields!",500, [
                'errors' => $validator->errors()
            ]);

        $modifier = FormActionHelper::getFormTimeStamp();

        $model = new FamilyHistory();
        $has_noneDisease = $model->checkFamilyHistory($this->_encounter->encounter_no);

        if($data['disease_id']==1){
            if($has_noneDisease){
                throw new EhrException('Cannot select none again!');
            }
        }
        $model->encounter_no = $this->_encounter->encounter_no;
        $model->spin = $this->_spin;
        $model->disease_id = $data['disease_id'];
        $model->specific_disease_description = $data['specific_disease_description'];
        $model->is_specific = $data['specific_disease_description'] != '' ? 1 : 0;
        $model->is_deleted = self::IS_NOT_DELETED;
        $model->created_by = $modifier['modified_by'];
        $model->deleted_by = "";
        $model->deleted_at = $modifier['modified_dt'];

        if(!$model->save())
            throw new EhrException('Unable to save Family History ');

        $modifier = FormActionHelper::getModifier('', $modifier);
        return [
            'message'   => "Family History successfully added!",
            'data'      =>  $model,
            'modified_by'   => $modifier['modified_by'],
            'updated_at' => $modifier['modified_dt']
        ];
    }

    public function deleteFamilyHistory($data){
        if(!$this->permService->hasPastMedEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $rules = [
            'id' => 'required',
        ];

        $validator = validator($data, $rules);

        if($validator->fails())
            throw new EhrException("Invalid Fields!",500, [
                'errors' => $validator->errors()
            ]);


        $model = FamilyHistory::query()->where([
            ["encounter_no", $this->_encounter->encounter_no],
            ["id", $data['id']]
        ])->first();

        $model->is_deleted = self::IS_DELETED;
        $model->deleted_at = date('Y-m-d h:m:s');
        $model->deleted_by = Auth::user()->personnel_id;

        if(!$model->save())
            throw new EhrException('Unable to delete Past Medical History ');

        return [
            'message'   => "Past Medical History successfully deleted!",
            'data'      =>  $data
        ];
    }

    public function actionSocialHistory($data){

        if(!$this->permService->hasPastMedEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $model = $this->_encounter->socialHistory;

        $modifier = FormActionHelper::getFormTimeStamp();

        if(empty($model)){
            $model = new SocialHistory();
            $model->encounter_no = $this->_encounter->encounter_no;
            $model->spin = $this->_spin;
            $model->is_deleted = self::IS_NOT_DELETED;
            $model->created_at = $modifier['modified_dt'];
            $model->created_by = $modifier['modified_by'];
        }
        $model->is_smoke = $data['is_smoke'];
        $model->years_smoking = $data['years_smoking'];
        $model->stick_per_day = $data['stick_per_day'];
        $model->stick_per_year = $data['stick_per_year'];
        $model->is_alcohol = $data['is_alcohol'];
        $model->is_drug = $data['is_drug'];
        $model->no_bottles = $data['no_bottles'];
        $model->remarks = $data['remarks'];
        $model->modified_at = $modifier['modified_dt'];
        $model->modified_by = $modifier['modified_by'];
        $model->updated_at = $modifier['modified_dt'];
        if(!$model->save())
            throw new EhrException('Unable to save Social History ');


        $modifier = FormActionHelper::getModifier('', $modifier);
        return [
            'message'   => "Social History successfully saved!",
            'data'      =>  $model,
            'modified_by'   => $modifier['modified_by'],
            'updated_at' =>  $modifier['modified_dt'],
        ];
    }

    public function actionMenstrualHistory($data){
        if(!$this->permService->hasPastMedEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $rules = [
            'last_period_menstrual' => 'required_if:is_applicable_menstrual,Y',
            'remarks' => 'max:2000',
            'birth_control_used' => 'max:2000',
        ];

        $validator = validator($data, $rules);

        if($validator->fails())
            throw new EhrException("Invalid Fields!",500, [
                'errors' => $validator->errors()
            ]);

        $model = $this->_encounter->menstrualHistory;

        $modifier = FormActionHelper::getFormTimeStamp();

        if(empty($model)){
            $model = new MenstrualHistory();
            $model->encounter_no = $this->_encounter->encounter_no;
            $model->spin = $this->_spin;
            $model->is_deleted = self::IS_NOT_DELETED;
            $model->created_at = $modifier['modified_dt'];
            $model->created_by = $modifier['modified_by'];
        }
        $model->is_applicable_menstrual = $data['is_applicable_menstrual'];
        $model->age_first_menstrual = $data['age_first_menstrual'];
        $model->last_period_menstrual = $data['last_period_menstrual'];
        $model->no_days_menstrual_period = $data['no_days_menstrual_period'];
        $model->interval_menstrual_period = $data['interval_menstrual_period'];
        $model->no_pads = $data['no_pads'];
        $model->age_sex_intercourse = $data['age_sex_intercourse'];
        $model->birth_control_used = $data['birth_control_used'] ? $data['birth_control_used'] : '';
        $model->is_menopause = $data['is_menopause'];
        $model->age_menopause = $data['age_menopause'];
        $model->remarks = $data['remarks'] ? $data['remarks'] : '';
        $model->modified_at = $modifier['modified_dt'];
        $model->updated_at = $modifier['modified_dt'];
        $model->modified_by = $modifier['modified_by'];
        if(!$model->save())
            throw new EhrException('Unable to save Menstrual History ');

        $modifier = FormActionHelper::getModifier('', $modifier);
        return [
            'message'   => "Menstrual History successfully saved!",
            'data'      =>  $model,
            'modified_by'   => $modifier['modified_by'],
            'updated_at' => $modifier['modified_dt']
        ];
    }

    public function actionPregnantHistory($data){
        if(!$this->permService->hasPastMedEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $rules = [
            'date_gravidity' => 'required_if:is_applicable_pregnant,Y',
            'date_parity' => 'required_if:is_applicable_pregnant,Y',
            'remarks' => 'max:2000',
        ];

        $validator = validator($data, $rules);

        if($validator->fails())
            throw new EhrException("Invalid Fields!",500, [
                'errors' => $validator->errors()
            ]);

        $modifier = FormActionHelper::getFormTimeStamp();

        $model = $this->_encounter->pregnantHistory;
        if(empty($model)){
            $model = new PregnantHistory();
            $model->encounter_no = $this->_encounter->encounter_no;
            $model->spin = $this->_spin;
            $model->is_deleted = self::IS_NOT_DELETED;
            $model->created_at = $modifier['modified_dt'];
            $model->created_by = $modifier['modified_by'];
        }
        $model->is_applicable_pregnant = $data['is_applicable_pregnant'];
        $model->date_gravidity = $data['date_gravidity'];
        $model->date_parity = $data['date_parity'];
        $model->type_delivery = $data['type_delivery'];
        $model->no_full_term_preg = $data['no_full_term_preg'];
        $model->no_premature = $data['no_premature'];
        $model->no_abortion = $data['no_abortion'];
        $model->no_living_children = $data['no_living_children'];
        $model->induced_hyper = $data['induced_hyper'];
        $model->family_planning = $data['family_planning'];
        $model->remarks = $data['remarks'];
        $model->modified_at = $modifier['modified_dt'];
        $model->created_at = $modifier['modified_dt'];
        $model->updated_at = $modifier['modified_dt'];
        $model->modified_by = $modifier['modified_by'];

        if(!$model->save())
            throw new EhrException('Unable to save Pregnant History ');

        $modifier = FormActionHelper::getModifier('', $modifier);
        return [
            'message'   => "Pregnant History successfully saved!",
            'data'      =>  $model,
            'updated_at' => $modifier['modified_dt'],
            'modified_by'   => $modifier['modified_by']
        ];
    }

    public function actionImmunizationRecord($data){
        if(!$this->permService->hasPastMedEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $rules = [
            'remarks' => 'max:2000',
        ];

        $validator = validator($data, $rules);

        if($validator->fails())
            throw new EhrException("Invalid Fields!",500, [
                'errors' => $validator->errors()
            ]);

        $modifier = FormActionHelper::getFormTimeStamp();

        $model = $this->_encounter->immunizationRecord;
        if(empty($model)){
            $model = new ImmunizationRecord();
            $model->encounter_no = $this->_encounter->encounter_no;
            $model->spin = $this->_spin;
            $model->is_deleted = self::IS_NOT_DELETED;
            $model->created_at = $modifier['modified_dt'];
            $model->created_by = $modifier['modified_by'];
        }
        $model->child_id = $data['child_id'];
        $model->young_id = $data['young_id'];
        $model->preg_id = $data['preg_id'];
        $model->elder_id = $data['elder_id'];
        $model->other_code = $data['other_code'];
        $model->remarks = $data['remarks'];
        $model->updated_at = $modifier['modified_dt'];
        $model->updated_by = $modifier['modified_by'];
        if(!$model->save())
            throw new EhrException('Unable to save Immunization Record ');


        $modifier = FormActionHelper::getModifier('', $modifier);
        return [
            'message'   => "Immunization Record successfully saved!",
            'data'      =>  $model,
            'modified_by'   => $modifier['modified_by'],
            'updated_at' => $modifier['modified_dt']
        ];
    }


    public static function getOptions()
    {
        return [
            'pastmedical' => [
                'disease' => self::getDiseases(),
            ],
            'familyhistory' => [
                'disease' => self::getDiseases(),
            ],
            'immunization' => [
                'childimm' => self::getImmChildData(),
                'adultimm' => self::getImmYoungData(),
                'elderlyimm' => self::getImmElderlyData(),
            ]
        ];
    }

    public static function config(){

        return [
            'm-patient-presentillness' => [
                'p-illness-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'p-illness-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
            ],
            'm-patient-pastmedicalhistory' => [
                'p-pastmedicalhistory-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'p-pastmedicalhistory-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
                'default-options' => [
                    'pastmedical' => [
                        // 'disease' => self::getDiseases(),
                        'disease' => [],
                    ]
                ]
            ],
            'm-patient-surgicalhistory' => [
                'p-surgicalhistory-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'p-surgicalhistory-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
            ],
            'm-patient-familyhistory' => [
                'p-familyhistory-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'p-familyhistory-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
                'default-options' => [
                    'familyhistory' => [
                        // 'disease' => self::getDiseases(),
                        'disease' => []
                    ]
                ]
            ],
            'm-patient-socialhistory' => [
                'p-socialhistory-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'p-socialhistory-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ]
            ],
            'm-patient-menstrual' => [
                'p-menstrual-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'p-menstrual-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ]
            ],
            'm-patient-pregnant' => [
                'p-pregnant-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'p-pregnant-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ]
            ],
            'm-patient-immunization' => [
                'p-immunization-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'p-immunization-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
                'default-options' => [
                    'immunization' => [
                        'childimm' => [],
                        'adultimm' => [],
                        'elderlyimm' => [],
                        // 'childimm' => self::getImmChildData(),
                        // 'adultimm' => self::getImmYoungData(),
                        // 'elderlyimm' => self::getImmElderlyData(),
                    ]
                ]
            ],
        ];
    }



}
