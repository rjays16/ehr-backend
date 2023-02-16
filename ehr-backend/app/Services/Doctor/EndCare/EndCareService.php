<?php


namespace App\Services\Doctor\EndCare;


use App\Exceptions\EhrException\EhrException;
use App\Models\DispositionCatalog;
use App\Models\Encounter;
use App\Models\EndCare;
use App\Services\Doctor\DrugsAndMedicine\PharmaService;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\Doctor\CF4Service;
use App\Services\Doctor\PMH\PastMedicalHistoryService;
use App\Services\FormActionHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EndCareService
{

    /**
     * @var Encounter $_encounter
     */
    public $_encounter;


    /** @var PermissionService $permService */
    public $permService;


    function __construct(Encounter $encounter=null)
    {
        $this->_encounter = $encounter;
        $this->permService = new PermissionService($encounter);
    }

    public static function init($encounter){
        $encounter = Encounter::query()->find($encounter);
        if (is_null($encounter))
            throw new EhrException('Encounter was not found. ');

        return new EndCareService($encounter);
    }

    public function patientEndCare(){
        $endCare = $this->_encounter->endcare;

        $modifier = FormActionHelper::getModifier('', [
            'modified_by' => $endCare ? $endCare->modify_id : '',
            'modified_dt' => $endCare ? $endCare->modify_dt : ''
        ]);
        
        return [
            'model' => $endCare,
            'data'      =>  [
                'encoded' => $modifier['modified_by'],
                'encoded_dt' => $modifier['modified_dt'],
            ]
        ];
    }

    public function actionEndCare($data){
        if(!$this->permService->hasEndCareEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $rules = [
            'reason' => 'required_if:treatment,5|max:2000',// required if treatment is Transfered (5)
            'doc_advice' => 'required|max:2000',
            'treatment' => 'required|exists:smed_disposition_catalog,id'
        ];
        $validator = validator($data, $rules);

        if($validator->fails())
            throw new EhrException("Invalid Fields!",500, [
                'errors' => $validator->errors()
            ]);

        $validation = CF4Service::init($this->_encounter->encounter_no);
        $convert_to_array = $validation->checkMandatoryFields();
        
        // remove end of care form mandatory messages
        foreach ($convert_to_array as $key => $value) {
            if(stristr(strtolower($value),'end') != false){
                unset($convert_to_array[$key]);
            }
        }

        if(empty($convert_to_array[count($convert_to_array)-1])) {
            unset($convert_to_array[count($convert_to_array)-1]);
        }
                
        if(!empty($convert_to_array)){
            throw new EhrException('These are the fields that still need encoding', 303, ["mandatories" => $convert_to_array]);
        }
        
        $model = $this->_encounter->endcare;

        $modifier = FormActionHelper::getFormTimeStamp();
        if(is_null($model)){
            $model = new EndCare();
            $model->encounter_no = $this->_encounter->encounter_no;
            $model->create_id = $modifier['modified_by'];
            $model->create_dt = $modifier['modified_dt'];
        }
        $model->date = date('Y-m-d');
        $model->time = date('H:i:s');
        $model->treatment = $data['treatment'];
        $model->reason = $data['reason'];
        $model->doc_advice = $data['doc_advice'];
        $model->modify_id = $modifier['modified_by'];
        $model->modify_dt = $modifier['modified_dt'];

        if(!$model->save())
            throw new EhrException('Unable to save end of care ', 500);

        $modifier = FormActionHelper::getModifier('',$modifier);
        return [
            'message'   => "End Care Successfully saved!",
            'data'      =>  [
                'encoded' => $modifier['modified_by'],
                'encoded_dt' => $modifier['modified_dt'],
            ]
        ];
    }

    public static function getTreatment(){
        $model = new DispositionCatalog();
        $disp = $model->getAllOptions();
        $menu = [];
        foreach($disp as $key => $entry){
            $menu[$entry['id']] = $entry['text'];
        }
        return $menu;
    }

    public static function config(){
        return [
            'm-patient-endofcare' => [
                'p-endofcare-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'p-endofcare-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
                'default-options' => [
                    'outcome' => self::getTreatment()
                ]
            ],
        ];
    }

}