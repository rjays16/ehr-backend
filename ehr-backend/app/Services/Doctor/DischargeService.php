<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 10/5/2019
 * Time: 4:33 PM
 */

namespace App\Services\Doctor;


use App\Models\AreaCatalog;
use App\Models\Encounter;
use App\Models\PersonnelCatalog;
use App\Services\Doctor\Permission\PermissionService;

class DischargeService
{
    /** @var Encounter $_encounter */
    private $_encounter;

    public function __construct(Encounter $encounter)
    {
        $this->_encounter = $encounter;
    }

    public static function config()
    {
        return [
                'm-patient-discharge' => [
                        'p-patient-discharge-view' => [
                                'role_name'         => [],
                                'other-permissions' => [],
                        ],
                        'p-patient-discharge-save' => [
                                'role_name'         => [
                                        PermissionService::$doctor,
                                ],
                                'other-permissions' => [],
                        ],
                        'default-options'           => [
                                'discharge-department' => self::department(),
                                'discharge-physician' => self::physician()
                        ],
                ],
        ];
    }

    public static function department()
    {
        $model = new AreaCatalog();
        $transaction = $model->department();
        $menu = [];
        foreach ($transaction as $key => $entry) {
            $menu[] = [
                'id' => $entry['id'],
                'area_desc' => $entry['area_desc']
            ];
        }

        return $menu;
    }

    public static function physician()
    {
        $model = new PersonnelCatalog();
        $transaction = $model->List_Doctors();
        $menu = [];
        foreach ($transaction as $key => $entry) {
            $menu[] = [
                'id' => $entry->personnel_id,
                'doctor_name' => $entry->doctor_name
            ];
        }
        return $menu;
    }
}