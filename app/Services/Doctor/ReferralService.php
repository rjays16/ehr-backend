<?php
/**
 * Created by PhpStorm.
 * User: debzl
 * Date: 8/26/2019
 * Time: 9:05 PM
 */

namespace App\Services\Doctor;

use App\Exceptions\EhrException\EhrException;
use App\Models\AreaCatalog;
use App\Models\BatchOrderNote;
use App\Models\DeptEncounter;
use App\Models\Encounter;
use App\Models\ReferralOrder;
use App\Models\ReferralPurposeCatalog;
use App\Services\Doctor\Permission\PermissionService;

class ReferralService
{

    /** @var Encounter $_encounter */
    private $_encounter;

    public function __construct(Encounter $encounter)
    {
        $this->_encounter = $encounter;
    }

    public static function init($enc){
        $enc = Encounter::query()->find($enc);
        if(is_null($enc))
            throw new EhrException('Encounter not found', 404);

        return new ReferralService($enc);
    }

    public function getAllReferrals()
    {
        $enc = $this->_encounter;

        return ReferralOrder::query()
                ->with('refto')
                ->whereHas('batch', function($join) use ($enc){
                    $join->where('encounter_no',$enc->encounter_no)
                    ->where('is_finalized',1);
                })->get()->map(function($item){
                    $item->data = json_decode($item->data);
                    $item->data->{'department_desc'} = $item->department ? $item->department->area_desc : '';
                    $item->data->{'area_desc'} = $item->doctorArea ? $item->doctorArea->area_desc : '';
                    
                    $purpose = ReferralPurposeCatalog::query()->find($item->data->reason);
                    $item->data->{'reason_desc'} = $purpose ? $purpose->purpose_desc : '';

                    $item->{'referto_doctor_desc'} = $item->refto->p->fullname();
                    unset($item->refto);
                    return $item;
                });
    }

    public static function config()
    {
        return [
                'm-patient-referral' => [
                        'p-patient-referral-view' => [
                                'role_name'         => [],
                                'other-permissions' => [],
                        ],
                        'p-patient-referral-save' => [
                                'role_name'         => [
                                        PermissionService::$doctor,
                                ],
                                'other-permissions' => [],
                        ],
                        'default-options'         => [
                                'refer-types' => [
                                    'department' => ' Interfacility' 
                                ],
                                'refer-department' => self::getDepartment(),
                                'refer-reason'     => self::getReason(),
                        ],
                ],
        ];
    }

    public static function getDepartment()
    {
        $model = new AreaCatalog();
        return  $model->department();
    }

    public static function getReason()
    {
        $model = new ReferralPurposeCatalog();
        $transaction = $model->reason();
        $menu = [];
        foreach ($transaction as $key => $entry) {
            $menu[] = [
                'id' => $entry['id'],
                'purpose_desc' => $entry['purpose_desc']
            ];
        }

        return $menu;
    }

}