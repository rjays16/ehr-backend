<?php

namespace App\API\V1\Controllers\HisServer;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Router;

class ServerController extends Controller
{

    private static $_routes;
    public static function  init(Router $api){
        $api->get('/authService/urs', ServerController::class . '@serverUrls');

        foreach (self::_getPaths() as $key => $routes){
            if(isset($routes['class'])){
                if(strtolower($routes['method']) == 'get')
                    $api->get(str_replace('/api','', $routes['url']), $routes['class']);
                else
                    $api->post(str_replace('/api','', $routes['url']), $routes['class']);
            }
        }

    }

    public function serverUrls(Request $request){
        return response()->json(collect(self::$_routes)->map(function ($r){
            unset($r['class']);
            return $r;
        }));
    }

    /*
     * Define the class name and action method here.
     * as value of [class] element
     * */
    public  static  function _getPaths(){

        self::$_routes = [
            //fn name => url path
            'encounter_getRecentEncounter'         => [
                'url'    => '/api/encounter/recent',
                'method' => 'GET',
//                'class' => ServerController::class . '@serverUrls'
            ],
            'cancelEncounter' => [
                'url'    => '/api/encounter/cancelthisencounter',
                'method' => 'POST',
            ],
            'patient_getpatientdatacf4'            => [
                'url'    => '/api/patient/getpatientdatacf4',
                'method' => 'GET',
            ],
            'patient_getsoap'                      => [
                'url'    => '/api/patient/getsoap',
                'method' => 'GET',
            ],
            'pharma_savepharmaitem'                => [
                'url'    => '/api/pharma/savepharmaitem',
                'method' => 'POST',
            ],
            'patient_getsoapsubjective'            => [
                'url'    => '/api/patient/getsoapsubjective',
                'method' => 'POST',
            ],
            'patient_getsoapobjective'             => [
                'url'    => '/api/patient/getsoapobjective',
                'method' => 'POST',
            ],
            'patient_getsoapfinaldiag'             => [
                'url'    => '/api/patient/getsoapfinaldiag',
                'method' => 'POST',
            ],
            'patient_getsoapother'                 => [
                'url'    => '/api/patient/getsoapother',
                'method' => 'POST',
            ],
            'patient_savesoapclinicalimp'          => [
                'url'    => '/api/patient/savesoapclinicalimpression',
                'method' => 'POST',
            ],
            'patient_savesoapfinaldiag'            => [
                'url'    => '/api/patient/savesoapfinaldiag',
                'method' => 'POST',
            ],
            'patient_savesoapotherdiag'            => [
                'url'    => '/api/patient/savesoapotherdiag',
                'method' => 'POST',
            ],
            'patient_savesoapobjective'            => [
                'url'    => '/api/patient/savesoapobjective',
                'method' => 'POST',
            ],
            'patient_savesoapplan'                 => [
                'url'    => '/api/patient/savesoapplan',
                'method' => 'POST',
            ],
            'patient_savesoapchiefother'           => [
                'url'    => '/api/patient/savesoapchiefothers',
                'method' => 'POST',
            ],
            'patient_savesoapassesmenticd'         => [
                'url'    => '/api/patient/savesoapassessmenticd',
                'method' => 'POST',
            ],
            'patient_removesoapassesmenticd'       => [
                'url'    => '/api/patient/removesoapassessmenticd',
                'method' => 'POST',
            ],
            'patient_addvitalsign'                 => [
                'url'    => '/api/patient/addvitalsign',
                'method' => 'POST',
            ],
            'patient_removevitalsign'              => [
                'url'    => '/api/patient/removevitalsign',
                'method' => 'POST',
            ],
            'patient_getsoapplan'                  => [
                'url'    => '/api/patient/getsoapplan',
                'method' => 'POST',
            ],
            'patient_getclinicalimp'               => [
                'url'    => '/api/patient/getclinicalimp',
                'method' => 'POST',
            ],
            'postAddPerson'                        => [
                'url'    => '/api/patient/postAddPerson',
                'method' => 'POST',
            ],
            'getAddPersonData'                        => [
                'url'    => '/api/patient/getAddPersonData',
                'method' => 'POST',
            ],
            'postAssignPatient'                    => [
                'url'    => '/api/patient/assignPatient',
                'method' => 'POST',
            ],
            'postDischargedPatient'                => [
                'url'    => '/api/patient/postDischargedPatient',
                'method' => 'POST',
            ],
            'postDeactivatePersonnel'              => [
                'url'    => '/api/patient/postDeactivatePersonnel',
                'method' => 'POST',
            ],
            'doctor_postCreatePersonnel'           => [
                'url'    => '/api/doctor/postCreatePersonnel',
                'method' => 'POST',
            ],
            'doctor_postCreatePersonnelAssignment' => [
                'url'    => '/api/doctor/postCreatePersonnelAssignment',
                'method' => 'POST',
            ],
            'doctor_postCreateUser'                => [
                'url'    => '/api/doctor/postCreateUser',
                'method' => 'POST',
            ],
            'doctor_postChangePassword'            => [
                'url'    => '/api/doctor/postChangePassword',
                'method' => 'POST',
            ],
            'postLaboratoryRequest'                => [
                'url'    => '/api/lab/postLaboratoryRequest',
                'method' => 'POST',
            ],
            'postRemoveLabRequest'                 => [
                'url'    => '/api/lab/removeLabRequest',
                'method' => 'POST',
            ],
            'postRemoveLabRequestPerItem'          => [
                'url'    => '/api/lab/removeLabRequestPerItem',
                'method' => 'POST',
            ],
            'postServeLabRequest'                  => [
                'url'    => '/api/lab/serveLabRequest',
                'method' => 'POST',
            ],
            'postRadRequest'                       => [
                'url'    => '/api/rad/postRadRequest',
                'method' => 'POST',
            ],
            'postServeRadRequest'                  => [
                'url'    => '/api/rad/postServeRadRequest',
                'method' => 'POST',
            ],
            'postRemoveRadRequest'                 => [
                'url'    => '/api/rad/removeRadRequest',
                'method' => 'POST',
            ],
            'postRemoveRadRequestPerItem'          => [
                'url'    => '/api/rad/removeRadRequestPerItem',
                'method' => 'POST',
            ],
            'postRemoveUnservedRad'          => [
                'url'    => '/api/rad/postRemoveUnservedRad',
                'method' => 'POST',
            ],
            'postPharmaRequest'                    => [
                'url'    => '/api/pharma/postPharmaRequest',
                'method' => 'POST',
            ],
            'postPharmaRemoveRequest'              => [
                'url'    => '/api/pharma/postPharmaRemoveRequest',
                'method' => 'POST',
            ],
            'postRemovePharmaRequestBatch'         => [
                'url'    => '/api/pharma/postRemovePharmaRequestBatch',
                'method' => 'POST',
            ],
            'postServePharma'                      => [
                'url'    => '/api/pharma/postServePharma',
                'method' => 'POST',
            ],
            'postSpecialLabRequest'                => [
                'url'    => '/api/lab/postLaboratoryRequest',
                'method' => 'POST',
            ],
            'postAddEncounterPerson_er'            => [
                'url'    => '/api/patient/postAddPerson_er',
                'method' => 'POST',
            ],
            'hci_postHciReferral'                  => [
                'url'    => '/api/hciReferral/postHciReferral',
                'method' => 'POST',
            ],
            'postDeleteLabRequest'                 => [
                'url'    => '/api/lab/deleteLabRequest',
                'method' => 'POST',
            ],
            'postDeleteRadioRequest'               => [
                'url'    => '/api/rad/deleteRadioRequest',
                'method' => 'POST',
            ],
            'postDeletePharmaRequest'              => [
                'url'    => '/api/pharma/deletePharmaRequest',
                'method' => 'POST',
            ],
        ];
        return self::$_routes;
    }
}
