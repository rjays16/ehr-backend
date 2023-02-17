<?php


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\API\V1\Controllers\IndexController;
use App\API\V1\Services\EhrResponse;
use App\Http\Controllers\Controller;

/**
 * Force all exception response to json format
 */
app('Dingo\Api\Exception\Handler')->register(
    function (Exception $e) {
        $resp = EhrResponse::responseExceptionFormat($e);
        return Illuminate\Http\JsonResponse::create(
            $resp['data'],
            in_array($resp['code'], [500, 400, 404, 401, 200]) ? $resp['code']
                : 500
        );
    }
);

$api = app('Dingo\Api\Routing\Router');
/** @var Dingo\Api\Routing\Router $api */


$api->version(
    'v1',
    function () use ($api) {

        /* HEALTH CHECK */
        $api->post('/', IndexController::class.'@healthCheck');
    }
);


$api->version(
    'v1',
    ['middleware' => ['jsonstring.to.post', 'cors.api']],
    function () use ($api) {
        /*Authentication */
        $api->post(
            '/login',
            \App\Api\V1\Controllers\Auth\LoginController::class.'@login'
        );

    }
);


$api->version(
    'v1',
    [
        'middleware' => [
            'jsonstring.to.post', 'cors.api', 'mobile.token.api', 'auth.api',
        ],
    ],
    function () use ($api) {
        /** App configuration and Defaults options that needs to be cache on client side */
        $api->get(
            '/config/n/defaults',
            App\API\V1\Controllers\ConfigurationController::class.'@getConfig'
        );
    }
);

$api->version(
    'v1',
    [
        'middleware' => [
            'jsonstring.to.post', 'cors.api', 'mobile.api', 'auth.api',
        ],
    ],
    function () use ($api) {
        $api->get('/mobile/manual/file/path', \App\API\V1\Controllers\ReportsController::class . '@mobileManualFilepath');



        /* Logout API*/
        $api->post(
            '/logout',
            \App\API\V1\Controllers\Auth\LogoutController::class.'@logout'
        );

        /* List of Patient API*/
        $api->get(
            '/doctor/patient/lists',
            \App\API\V1\Controllers\Doctor\DoctorController::class
            .'@searchAllDoctorPatients'
        );
        $api->get(
            '/doctor/favorite/patients',
            \App\API\V1\Controllers\Doctor\DoctorController::class
            .'@allDoctorTaggedPatients'
        );
        $api->post(
            '/doctor/tag/patient',
            \App\API\V1\Controllers\Doctor\DoctorController::class
            .'@tagPatient'
        );


        /** TELEMED */
        $api->get(
            '/notifications',
            \App\API\V1\Controllers\NotificationController::class
            .'@getNotifs'
        );

        $api->post(
            '/notifications/seen',
            \App\API\V1\Controllers\NotificationController::class
            .'@setNotifSeen'
        );

        $api->post(
            '/notifications/seen/by/refno',
            \App\API\V1\Controllers\NotificationController::class
            .'@setNotifSeenByRefNo'
        );

        $api->post(
            '/notifications/seen/mark/all',
            \App\API\V1\Controllers\NotificationController::class
            .'@setNotifSeenAll'
        );

        /* Patient History of Encounter API*/
        $api->get(
            '/doctor/patient/encounters',
            \App\API\V1\Controllers\Patient\PatientController::class
            .'@patientEncounterHistory'
        );

        $api->get(
            '/selectData/GetICDFinalDiagnosisWho',
            App\API\V1\Controllers\Diagnostic\DiagnosticController::class
            .'@searchIcds'
        );

        /*Doctors API*/

        $api->get(
            '/cf4/file/path',
            \App\API\V1\Controllers\ReportsController::class.'@getCf4Url'
        );
        $api->get(
            '/cf4/validate',
            \App\API\V1\Controllers\ReportsController::class.'@cf4Validate'
        );
        $api->get(
            '/doctor/patient/prescription/file/path',
            \App\API\V1\Controllers\ReportsController::class
            .'@getPrescriptionFilePath'
        );
        $api->get(
            '/doctor/patient/diagnostic/rad/report/pdf/file/path',
            \App\API\V1\Controllers\Diagnostic\DiagnosticController::class
            .'@getRadReportPdfFilePath'
        );
        $api->get(
            '/doctor/patient/diagnostic/lab/report/pdf/file/path',
            \App\API\V1\Controllers\Diagnostic\DiagnosticController::class
            .'@getLabReportPdfFilePath'
        );

        /*Diagnostic */
        $api->get(
            '/diagnostic/get/typecharge/data',
            \App\API\V1\Controllers\Doctor\PlanManagmentController::class
            .'@getTypeCharge'
        );

        /*Doctors Patient*/
        $api->get(
            '/doctor/patient/information',
            \App\API\V1\Controllers\Patient\PatientController::class
            .'@patientInfo'
        );

        /*Doctors Patient SOAP*/
        $api->get(
            '/doctor/patient/soap/options',
            \App\API\V1\Controllers\Doctor\SoapController::class
            .'@getSoapOtions'
        );
        $api->get(
            '/doctor/patient/soap/data',
            \App\API\V1\Controllers\Doctor\SoapController::class
            .'@getSoapData'
        );
        $api->get(
            '/doctor/patient/soap/assessment/diagnosis/trail',
            \App\API\V1\Controllers\Doctor\SoapController::class
            .'@getAssessmentDiagnosisTrail'
        );

        /** Past Medical History */
        $api->get(
            '/doctor/patient/pmh/presentillness/options',
            \App\API\V1\Controllers\Doctor\PastMedicalController::class
            .'@getOptions'
        );
        $api->get(
            '/doctor/patient/pmh/presentillness/data',
            \App\API\V1\Controllers\Doctor\PastMedicalController::class
            .'@patientPresentIllness'
        );
        $api->get(
            '/doctor/patient/pmh/pastmedicalhistory/data',
            \App\API\V1\Controllers\Doctor\PastMedicalController::class
            .'@patientPastMedicalHistory'
        );
        $api->get(
            '/doctor/patient/pmh/surgicalhistory/data',
            \App\API\V1\Controllers\Doctor\PastMedicalController::class
            .'@patientSurgicalHistory'
        );
        $api->get(
            '/doctor/patient/pmh/familyhistory/data',
            \App\API\V1\Controllers\Doctor\PastMedicalController::class
            .'@patientFamilyHistory'
        );
        $api->get(
            '/doctor/patient/pmh/socialhistory/data',
            \App\API\V1\Controllers\Doctor\PastMedicalController::class
            .'@patientSocialHistory'
        );
        $api->get(
            '/doctor/patient/pmh/immunizationrecord/data',
            \App\API\V1\Controllers\Doctor\PastMedicalController::class
            .'@patientImmunizationRecord'
        );
        $api->get(
            '/doctor/patient/pmh/gynecologicalobstetrichistory/data',
            \App\API\V1\Controllers\Doctor\PastMedicalController::class
            .'@patientGynecologicalObstetricHistory'
        );

        /** Medication */
        $api->get(
            '/doctor/patient/doctor/pharma/getdata',
            \App\API\V1\Controllers\Doctor\PharmaController::class
            .'@patientMedication'
        );
        $api->get(
            '/doctor/patient/doctor/pharma/defaultOptions',
            \App\API\V1\Controllers\Doctor\PharmaController::class
            .'@defaultOptions'
        );
        $api->get(
            '/doctor/patient/doctor/pharma/getMedication',
            \App\API\V1\Controllers\Doctor\PharmaController::class
            .'@getMedication'
        );

        /** End Care */
        $api->get(
            '/doctor/patient/endcare/getdata',
            \App\API\V1\Controllers\Doctor\EndCareController::class
            .'@patientEndCare'
        );

        /** Pertinent Signs and Symptoms */
        $api->get(
            '/doctor/patient/psigns/options',
            \App\API\V1\Controllers\Doctor\PertinentSignsController::class
            .'@getPertinentSignsAndSympOptions'
        );
        $api->get(
            '/doctor/patient/psigns/get',
            \App\API\V1\Controllers\Doctor\PertinentSignsController::class
            .'@getPertinentSignsAndSympData'
        );
        $api->get(
            '/doctor/patient/psigns/others/options',
            \App\API\V1\Controllers\Doctor\PertinentSignsController::class
            .'@pertinentSignsAndSympDefaultOthersOptions'
        );
        $api->get(
            '/doctor/patient/psigns/pains/options',
            \App\API\V1\Controllers\Doctor\PertinentSignsController::class
            .'@pertinentSignsAndSympDefaultPainsOptions'
        );

        /** Physical Examination */
        $api->post(
            '/doctor/patient/physical/examination/data',
            \App\API\V1\Controllers\Doctor\PhysicalExaminationController::class
            .'@getPatientData'
        );

        /** Prescription Form */
        $api->post(
            '/doctor/medicine/search',
            \App\API\V1\Controllers\Doctor\PrescriptionController::class
            .'@searchMeds'
        );
        $api->post(
            '/doctor/prescription/options',
            \App\API\V1\Controllers\Doctor\PrescriptionController::class
            .'@getDefaultOptions'
        );


        /** Referral from other HCI */
        $api->get(
            '/doctor/patient/reffromother/hci/data',
            \App\API\V1\Controllers\Doctor\RefFromOtherHCIController::class
            .'@getData'
        );


        /** Referrals Orders */
        $api->get(
            '/doctor/patient/referrals/data',
            \App\API\V1\Controllers\Doctor\PlanManagmentController::class
            .'@getAllReferralOrders'
        );


        /** Plan Management */
        $api->post(
            '/doctor/planmanagement/getAllOrders',
            \App\API\V1\Controllers\Doctor\PlanManagmentController::class
            .'@getAllOrders'
        );
        $api->post(
            '/doctor/planmanagement/finalize/orders',
            \App\API\V1\Controllers\Doctor\PlanManagmentController::class
            .'@finalizedOrder'
        );


        /** Diagnostic Orders Results and Status */
        $api->get(
            '/doctor/patient/diagnostic/orders',
            App\API\V1\Controllers\Diagnostic\DiagnosticController::class
            .'@getResults'
        );
        $api->get(
            '/doctor/patient/diagnostic/rad/report/pacs',
            App\API\V1\Controllers\Diagnostic\DiagnosticController::class
            .'@getRadPacsUrl'
        );


        /** Define all api's that are accessible for all users above from here. */

        $api->group(
            ['middleware' => ['doctor']], function () use ($api) {
            /*Doctors Patient SOAP*/
            $api->post(
                '/doctor/patient/soap/objective/save',
                \App\API\V1\Controllers\Doctor\SoapController::class
                .'@saveSoapObjective'
            );
            $api->post(
                '/doctor/patient/soap/plan/save',
                \App\API\V1\Controllers\Doctor\SoapController::class
                .'@saveSoapPlan'
            );
            $api->post(
                '/doctor/patient/soap/subjective/save',
                \App\API\V1\Controllers\Doctor\SoapController::class
                .'@saveSoapSubjective'
            );
            $api->post(
                '/doctor/patient/soap/assessment/diagnosis/save',
                \App\API\V1\Controllers\Doctor\SoapController::class
                .'@saveSoapAsseessmentDiagnosis'
            );
            $api->post(
                '/doctor/patient/soap/assessment/clinicalimp/save',
                \App\API\V1\Controllers\Doctor\SoapController::class
                .'@saveSoapAsseessmentClinicalImp'
            );
            $api->post(
                '/doctor/patient/soap/assessment/icd/save',
                \App\API\V1\Controllers\Doctor\SoapController::class
                .'@saveSoapAssessmentIcd10'
            );
            $api->post(
                '/doctor/patient/soap/assessment/icd/lists/save',
                \App\API\V1\Controllers\Doctor\SoapController::class
                .'@saveSoapAssessmentIcd10List'
            );
            $api->post(
                '/doctor/patient/soap/assessment/icd/delete',
                \App\API\V1\Controllers\Doctor\SoapController::class
                .'@removeSoapAssessmentIcd10'
            );

            $api->post(
                '/doctor/patient/endcare/treatment/options',
                \App\API\V1\Controllers\Doctor\EndCareController::class
                .'@getTreatmentOptions'
            );

            /** Past Medical History */
            $api->post(
                '/doctor/patient/pmh/presentillness/action',
                \App\API\V1\Controllers\Doctor\PastMedicalController::class
                .'@actionPresentIllness'
            );
            $api->post(
                '/doctor/patient/pmh/pastmedicalhistory/action',
                \App\API\V1\Controllers\Doctor\PastMedicalController::class
                .'@actionPastMedicalHistory'
            );
            $api->post(
                '/doctor/patient/pmh/pastmedicalhistory/delete',
                \App\API\V1\Controllers\Doctor\PastMedicalController::class
                .'@deletePastMedicalHistory'
            );
            $api->post(
                '/doctor/patient/pmh/surgicalhistory/action',
                \App\API\V1\Controllers\Doctor\PastMedicalController::class
                .'@actionSurgicalHistory'
            );
            $api->post(
                '/doctor/patient/pmh/surgicalhistory/delete',
                \App\API\V1\Controllers\Doctor\PastMedicalController::class
                .'@deleteSurgicalHistory'
            );
            $api->post(
                '/doctor/patient/pmh/familyhistory/action',
                \App\API\V1\Controllers\Doctor\PastMedicalController::class
                .'@actionFamilyHistory'
            );
            $api->post(
                '/doctor/patient/pmh/familyhistory/delete',
                \App\API\V1\Controllers\Doctor\PastMedicalController::class
                .'@deleteFamilyHistory'
            );
            $api->post(
                '/doctor/patient/pmh/socialhistory/action',
                \App\API\V1\Controllers\Doctor\PastMedicalController::class
                .'@actionSocialHistory'
            );
            $api->post(
                '/doctor/patient/pmh/menstrualhistory/action',
                \App\API\V1\Controllers\Doctor\PastMedicalController::class
                .'@actionMenstrualHistory'
            );
            $api->post(
                '/doctor/patient/pmh/pregnanthistory/action',
                \App\API\V1\Controllers\Doctor\PastMedicalController::class
                .'@actionPregnantHistory'
            );
            $api->post(
                '/doctor/patient/pmh/immunizationrecord/action',
                \App\API\V1\Controllers\Doctor\PastMedicalController::class
                .'@actionImmunizationRecord'
            );

            /** Vital Signs */
            $api->get(
                '/doctor/patient/vitalsign/getdata',
                \App\API\V1\Controllers\Doctor\VitalSignController::class
                .'@getVitalSignData'
            );

            /** Pertinent Signs and Symptoms */
            $api->post(
                '/doctor/patient/psigns/save',
                \App\API\V1\Controllers\Doctor\PertinentSignsController::class
                .'@savepertinentSignsAndSymp'
            );

            /** End Care */
            $api->post(
                '/doctor/patient/endcare/action',
                \App\API\V1\Controllers\Doctor\EndCareController::class
                .'@actionEndCare'
            );

            /** Physical Examination */
            $api->post(
                '/doctor/patient/physical/examination/data/save',
                \App\API\V1\Controllers\Doctor\PhysicalExaminationController::class
                .'@savePatientData'
            );

            /** Medication */
            $api->post(
                '/doctor/patient/medication/action',
                \App\API\V1\Controllers\Doctor\PharmaController::class
                .'@actionMedication'
            );


            /** Plan Management   => START */

            /** Prescription Form */
            $api->post(
                '/doctor/prescription/save',
                \App\API\V1\Controllers\Doctor\PrescriptionController::class
                .'@savePrescription'
            );


            /** Plan Management   => END */

            /** Referral from other HCI */
            $api->post(
                '/doctor/patient/reffromother/hci/data/save',
                \App\API\V1\Controllers\Doctor\RefFromOtherHCIController::class
                .'@saveData'
            );

            /* Repetitive Session */
            $api->post(
                '/doctor/repetitive/save',
                \App\API\V1\Controllers\Doctor\RepetitiveSessionController::class
                .'@saveRepetitiveSession'
            );
            $api->post(
                '/doctor/repetitive/delete',
                \App\API\V1\Controllers\Doctor\RepetitiveSessionController::class
                .'@deleteRepetitiveSession'
            );

            $api->post(
                '/doctor/finalized/repetitive/delete',
                \App\API\V1\Controllers\Doctor\RepetitiveSessionController::class
                .'@deleteRepetitiveCoursewardSession'
            );

            /* Diagnostic Order */


            $api->get(
                '/doctor/diagnostic/lab/configanddef',
                \App\API\V1\Controllers\Diagnostic\DiagnosticController::class
                .'@getDiagnosticConfigAndDefaults'
            );

            $api->get(
                '/doctor/diagnostic/rad/configanddef',
                \App\API\V1\Controllers\Diagnostic\DiagnosticController::class
                .'@getDiagnosticRadConfigAndDefaults'
            );

            $api->post(
                '/doctor/diagnostic/lab/save',
                \App\API\V1\Controllers\Diagnostic\DiagnosticController::class
                .'@saveDiagnosticLabOrders'
            );
            $api->post(
                '/doctor/diagnostic/rad/save',
                \App\API\V1\Controllers\Diagnostic\DiagnosticController::class
                .'@saveDiagnosticRadOrders'
            );

            $api->post(
                '/doctor/diagnostic/lab/delete',
                \App\API\V1\Controllers\Diagnostic\DiagnosticController::class
                .'@deleteDiagnosticLabOrders'
            );
            $api->post(
                '/doctor/diagnostic/rad/delete',
                \App\API\V1\Controllers\Diagnostic\DiagnosticController::class
                .'@deleteDiagnosticRadOrders'
            );

            /* Referral Order */
            $api->post(
                '/doctor/referral/save',
                \App\API\V1\Controllers\Doctor\ReferralController::class
                .'@saveReferralOrder'
            );
            $api->post(
                '/doctor/referral/delete',
                \App\API\V1\Controllers\Doctor\ReferralController::class
                .'@deleteReferralOrder'
            );

            /* Discharge Order */
            $api->post(
                '/doctor/discharge/save',
                \App\API\V1\Controllers\Doctor\DischargeController::class
                .'@saveDischargeOrder'
            );
            $api->post(
                '/doctor/discharge/delete',
                \App\API\V1\Controllers\Doctor\DischargeController::class
                .'@deleteDischargeOrder'
            );

            /* Courseward Order */
            $api->post(
                '/doctor/courseward/save',
                \App\API\V1\Controllers\Doctor\CoursewardController::class
                .'@saveCoursewardOrder'
            );
            $api->post(
                '/doctor/courseward/delete',
                \App\API\V1\Controllers\Doctor\CoursewardController::class
                .'@deleteCoursewardOrder'
            );
        }
        );

        /** Nurse */
        $api->get(
            '/nurse/patient/information',
            \App\API\V1\Controllers\Patient\PatientController::class
            .'@patientNurseInfo'
        );
        $api->get(
            '/nurse/carrymanagement/getAllOrders',
            \App\API\V1\Controllers\Nurse\CarryOutController::class
            .'@getAllOrders'
        );

        $api->post(
            '/nurse/carryoutOrders',
            \App\API\V1\Controllers\Nurse\CarryOutController::class
            .'@carryOutOrder'
        );


        $api->get(
            '/nurse/patient/lists',
            \App\API\V1\Controllers\Nurse\NurseController::class
            .'@searchAllNursePatients'
        );
        $api->get(
            '/nurse/wardlists/get',
            \App\API\V1\Controllers\Nurse\NurseController::class.'@getWards'
        );
        $api->post(
            '/nurse/dar/action',
            \App\API\V1\Controllers\Nurse\NurseController::class
            .'@actionDarNotes'
        );
        $api->post(
            '/nurse/dar/delete',
            \App\API\V1\Controllers\Nurse\NurseController::class
            .'@deleteDarNote'
        );
        $api->get(
            '/nurse/dar/get',
            \App\API\V1\Controllers\Nurse\NurseController::class
            .'@getDarNotes'
        );
        $api->post(
            '/nurse/dar/finalize',
            \App\API\V1\Controllers\Nurse\NurseController::class
            .'@finalizeNote'
        );
        $api->post(
            '/test/check/validate',
            \App\API\V1\Controllers\Doctor\DoctorController::class
            .'@checkIncompleData'
        );

    }
);

$api->version(
    'v1',
    ['middleware' => ['his.token', 'his.user']],
    function () use ($api) {
        /*This are for the api's for HIS request*/
        \App\API\V1\Controllers\HisServer\ServerController::init($api);
    }
);

//DR NOTES
$api->version(
    'v1',
    ['middleware' => [
            'jsonstring.to.post', 'cors.api', 'checkIp'
        ]
    ],
    function () use ($api) {
        /* List of Patient API*/
        $api->get(
            '/webservice/doctor/patient/lists',
            \App\API\V1\Controllers\Doctor\DoctorController::class
            .'@webserviceSearchAllPatients'
        );
    }
);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS, post, get');
header('Access-Control-Allow-Headers: Content-Type,x-requested-with, x-socket-id, authorization, Accept, Origin, Referer, User-Agent,  Access-Control-Request-Headers, Access-Control-Request-Method, Access-Control-Allow-Origin');

