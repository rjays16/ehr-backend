<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 10/5/2019
 * Time: 4:28 PM
 */

namespace App\Services\Doctor;

use App\Models\Encounter;
use App\Services\Doctor\Permission\PermissionService;

class CoursewardService
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
                'm-patient-courseward' => [
                        'p-patient-courseward-view' => [
                                'role_name'         => [],
                                'other-permissions' => [],
                        ],
                        'p-patient-courseward-save' => [
                                'role_name'         => [
                                        PermissionService::$doctor,
                                ],
                                'other-permissions' => [],
                        ],
                        'default-options'           => [],
                ],
        ];
    }
}