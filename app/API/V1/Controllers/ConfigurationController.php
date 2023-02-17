<?php

namespace App\API\V1\Controllers;

use App\API\V1\Services\EhrResponse;
use App\Models\Order\Diagnostic\LaboratoryService;
use App\Models\Order\Diagnostic\RadiologyService;
use App\Services\Doctor\CoursewardService;
use App\Services\Doctor\DischargeService;
use App\Services\Doctor\DrugsAndMedicine\PharmaService;
use App\Services\Doctor\EndCare\EndCareService;
use App\Services\Doctor\PMH\PastMedicalHistoryService;
use App\Services\Doctor\ReferralService;
use App\Services\Doctor\RepetitiveService;
use App\Services\Doctor\VitalSign\PreAssessmentService;
use App\Services\Nurse\NurseService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Doctor\DoctorService;
use App\Services\Doctor\ExaminationService;
use App\Services\Doctor\HCIServices;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\Doctor\PertinentSignAndSymptomsService;
use App\Services\Doctor\PrescriptionService;
use App\Services\Doctor\Soap\SoapService;

class ConfigurationController extends Controller
{
    public function getConfig()
    {
        return EhrResponse::jsonResponsePure([
            'patientlist' => DoctorService::config(),
            'prescription' => PrescriptionService::config(),
            'examination' => ExaminationService::config(),
            'soap' => SoapService::config(),
            'psigns' => PertinentSignAndSymptomsService::config(),
            'pastmedical' => PastMedicalHistoryService::config(),
            'ref_from_hci' => HCIServices::config(),
            'vitalsign' => PreAssessmentService::config(),
            'medication' => PharmaService::config(),
            'endcare' => EndCareService::config(),
            // 'laboratory' => LaboratoryService::config(),
            // 'radiology' => RadiologyService::config(),
            // 'bloodbank' => LaboratoryService::config(),
            // 'special_laboratory' => LaboratoryService::config(),
            'courseward' => CoursewardService::config(),
            'referral' => ReferralService::config(),
            'repetitive' => RepetitiveService::config(),
            'discharge' => DischargeService::config(),
            'wards' => NurseService::config(),
            'user-permissions' => collect(PermissionService::getAllEhrPermissions())
        ]);
    }
}
