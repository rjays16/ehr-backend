<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 10/5/2019
 * Time: 4:21 PM
 */

namespace App\Services\Doctor;

use App\Models\DiagnosisProcedure;
use App\Models\Encounter;
use App\Models\RepetitiveOrder;
use App\Services\Doctor\Permission\PermissionService;

class RepetitiveService
{

    /**
     * $proceduteList will only have value one self::getProcedure() is called
     * @var array $proceduteList
     */
    private static $proceduteList; 

    /** @var Encounter $_encounter */
    private $_encounter;

    public function __construct(Encounter $encounter)
    {
        $this->_encounter = $encounter;
    }

    public static function config()
    {
        return [
                'm-patient-repetitive' => [
                        'p-patient-repetitive-view' => [
                                'role_name'         => [],
                                'other-permissions' => [],
                        ],
                        'p-patient-repetitive-save' => [
                                'role_name'         => [
                                        PermissionService::$doctor,
                                ],
                                'other-permissions' => [],
                        ],
                        'default-options'           => [
                                'repetitive-procedure' => self::getProcedure(),
                        ],
                ],
        ];
    }

    public static function getProcedure()
    {
        if(!is_null(self::$proceduteList))
            return self::$proceduteList;

        $model = new DiagnosisProcedure();
        $transaction = $model->procedure();
        $menu = [];
        foreach ($transaction as $key => $entry) {
            $menu[] = [
                'id' => $entry['id'],
                'procedure' => $entry['procedure'],
                'rvs_code' => $entry['rvs_code'],
            ];
        }

        self::$proceduteList = $menu;
        return $menu;
    }
}