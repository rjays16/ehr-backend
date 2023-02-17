<?php


namespace App\Services\Doctor;


use App\Exceptions\EhrException\EhrException;
use App\Models\Encounter;
use App\Models\ReferralInstitution;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\FormActionHelper;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;

class HCIServices
{

    /**
     * @var Encounter $encounter
     */
    private $encounter;

    /** @var PermissionService $permService */
    public $permService;

    function __construct(Encounter $encounter)
    {
        $this->encounter = $encounter;
        $this->permService = new PermissionService($encounter);
    }

    /**
     * @var string $encounter
     * @return HCIServices
     */
    public static function init($encounter)
    {
        $enc = Encounter::query()->find($encounter);
        if(is_null($enc))
            throw new EhrException('Encounter not found', 404);

        return new HCIServices($enc);
    }

    /**
     * @return array
     */
    public function getData()
    {
        $data = collect([]);
        /**
         * @var ReferralInstitution $refInst
         */
        $refInst = $this->encounter->encounterRefHCI;
        $modified = FormActionHelper::getModifier('',[
            'modified_dt' => $refInst?$refInst->modify_dt:'',
            'modified_by' => $refInst?$refInst->modify_id:''
        ]);
        return $data->put('config', $refInst ? $refInst->is_hci : 0)
                ->put('referral_reason', $refInst ? $refInst->referral_reason : "")
                ->put('name_of_hci', $refInst ? $refInst->name_of_hci : "")
                ->put('data', [
                    'date_encoded' => $modified['modified_dt'],
                    'encoded' => $modified['modified_by'],
                    'modified_by_fullname' => $modified['modified_by_fullname']
                ])
                ->toArray();
    }



    /**
     * @var array $data => [
     *      reason => ''
     *      hci_name => ''
     *      isHCI => 1 or 0
     * ]
     * @return array
     */
    public function updateRefferedHCI($data){
        if(!$this->permService->hasRefHciEdit())
            throw new EhrException(PermissionService::$errorMessage, PermissionService::$errorCode);

        $validator = validator($data, [
            'reason' => 'required_if:isHCI,1|max:2000',
            'hci_name' => 'required_if:isHCI,1|max:2000',
            'isHCI' => [
                'required',
                Rule::in([0,1])
            ],
        ]);
        if($validator->fails())
            throw new EhrException('Some fields is invalid',500, [
                'errors' => $validator->errors()->getMessages()
            ]);


        /**
         * @var ReferralInstitution $model
         */
        $model = $this->encounter->encounterRefHCI;

        $modified = FormActionHelper::getFormTimeStamp();

        if(!$model){
            $model = new ReferralInstitution();
            $model->encounter_no = $this->encounter->encounter_no;
            $model->create_dt = $modified['modified_dt'];
            $model->create_id = $modified['modified_by'];
            $message = 'Referred HCI reason saved!';
        }
        else{
            $message = 'Referred HCI reason updated!';
        }

        $model->modify_dt = $modified['modified_dt'];
        $model->modify_id = $modified['modified_by'];
        $model->name_of_hci = $data['hci_name'];
        $model->referral_reason = $data['reason'];
        $model->is_hci = $data['isHCI'];


        if($model->save()){
            $modified = FormActionHelper::getModifier('', $modified);
            return [
                'msg' => $message,
                'data' => [
                    'encoded' => $modified['modified_by'],
                    'date_encoded' => $modified['modified_dt']
                ]
            ];
        }
        else
            throw new EhrException('Failed to save.', 500);
    }



    public static function config()
    {
        return [
            ' m-patient-reffromotherhci' => [
                'reffromotherhci-view' => [
                    'role_name' => [
                        PermissionService::$doctor,
                        PermissionService::$nurse
                    ],
                    'other-permissions' => []
                ],
                'reffromotherhci-save' => [
                    'role_name' => [
                        PermissionService::$doctor
                    ],
                    'other-permissions' => []
                ],
                'default-options' => []
            ]
        ];
    }
}
