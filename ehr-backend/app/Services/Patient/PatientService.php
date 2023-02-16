<?php
/**
 * Created by PhpStorm.
 * User: debzl
 * Date: 8/26/2019
 * Time: 9:05 PM
 */

namespace App\Services\Patient;


use App\Exceptions\EhrException\EhrException;
use App\Exceptions\His\HisActiveResource;
use App\Models\DeptEncounter;
use App\Models\Encounter;
use App\Models\FamilyHistory;
use App\Models\HIS\HisBillingEncounter;
use App\Models\ImmunizationRecord;
use App\Models\MenstrualHistory;
use App\Models\PatientCatalog;
use App\Models\PregnantHistory;
use App\Models\PresentIllness;
use App\Models\PastMedicalHistory;
use App\Models\SocialHistory;
use App\Models\SurgicalHistory;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\Doctor\Soap\SoapService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientService
{
    /**
     * @var Encounter
     */
    private $_encounter;

    public static $filterByEnc = 'encounter_no';
    public static $filterByPid = 'spin';

    function __construct($encounter)
    {
        $this->_encounter = Encounter::query()->find($encounter);
        if(!$this->_encounter)
            throw new EhrException('Encounter does not exist.', 404);
    }

    /**
     * @return Collection
    */
    public function getPatientInfo():Collection{

        $his = HisActiveResource::instance();
        $patient = $this->_encounter::query()->where('encounter_no',$this->_encounter->encounter_no)
            ->with([
            'spin0',
            'currentDeptEncounter.area',
        ])->first();

        if(!$patient)
            throw new EhrException('Patient encounter does not exist.');

        $is_final = (new HisBillingEncounter())->getFinalBill($this->_encounter->encounter_no);
        
        $patient->{'is_favorite'} = !is_null($patient->thisDoctorFavorite(auth()->user()->personnel_id)->first());
        $patient->{'hospital_days'} = $patient->spin0->getHospitalDate($this->_encounter);
        $patient->{'is_final'} = $is_final['is_final'] ? : 0;

        $patientInfo = $his->getPersonBasicInformation($this->_encounter->spin);
        if($patientInfo['status']){
            $patientInfo['data']['person_data']['age'] = $patient->spin0->getAge();;
            $patient->spin0->{'person_his'} = $patientInfo['data']['person_data'];
        }
        else
            $patient->spin0->{'person_his'} = [];

        $patient = $patient->toArray();
        
        $permServ = new PermissionService($this->_encounter);



        unset($patient['spin0']['person']);
        $data = collect($patient);
        $data=$data->map(function($value, $key){
            if($key == 'encounter_date')
                $value = strtotime($value);
            if($key == 'spin0' && isset($value['person_his']['dateOfBirth']))
                $value['person_his']['dateOfBirth'] = strtotime($value['person_his']['dateOfBirth']);

            return $value;
        });
        $data['isInMyDept'] = $permServ->isInMyDept();
        
        return $data;

//        $his = HisActiveResource::instance();
//        $patientInfo = $his->getPersonBasicInformation($this->_encounter->spin0->spin);
//        $response = $his->getResponseData();
//        return $patientInfo;
    }





    public function nursePatientInfo()
    {

        $physicians = $this->_encounter->getPhysicians();

        $soap = new SoapService($this->_encounter);
        $subjective = $soap->getCheifComplaintSelected();
        $subjective_others = $subjective['others']?($subjective['others']['value']?:''): '';



        return $this->getPatientInfo()
        ->merge([
            'attending_team' => $physicians
        ])->merge([
            'medical_info' => [
                'chiefComplaint' => "{$subjective['names']} - {$subjective_others}",
                'impression' => $soap->getClinicalImpression($this->_encounter->encounter_no)['clinical_imp'],
            ]
        ]);
    }



    public static function getEncounters($filterBy, $valueId)
    {
        $area = [];
        $dept = [];
        $user = auth()->user();
        $currentDept = $user->personnel->currentAssignment->area->dept_id;
        $currentArea = $user->personnel->currentAssignment->area->area_id;

        $is_dept = $currentDept == "0" ? "" : " AND sac.dept_id = $currentDept";
        $dept_code = $currentArea == $currentDept ? "" : "sro.dept_id = $currentArea OR sac.area_id = $currentArea";

        $hasViewAllPerm =  PermissionService::getAllEhrPermissions()->whereIn('id', PermissionService::permissionWithViewAll())->first() != null;

        $_sql = "AND ($dept_code $is_dept)";

        if($currentArea == $currentDept){
            foreach ($user->personnel->currentAssignment->area->depts as $area_one) {
                array_push($area, $area_one->area_id);
                array_push($dept, $area_one->dept_id);
                foreach ($area_one->depts as $area_two) {
                    array_push($area, $area_two->area_id);
                    array_push($dept, $area_two->dept_id);
                    foreach ($area_two->depts as $area_three) {
                        array_push($area, $area_three->area_id);
                        array_push($dept, $area_three->dept_id);
                    }
                }
            }

            $area_code = '';
            foreach (array_unique(array_merge(array_unique($area), array_unique($dept))) as $i => $area_catalog) {
                $or = $i == 0 ? "" : " OR ";
                $dept_code .= $or." sro.dept_id = $area_catalog ";
                $area_code .= $or." sac.area_id = $area_catalog ";
            }

            $_sql = " AND ($dept_code OR $area_code)";
        }

        $_sql = $hasViewAllPerm ? '':$_sql;

        $condition = "enc.encounter_no = '{$valueId}'";
        if(self::$filterByPid == $filterBy)
            $condition = "enc.spin = '{$valueId}'";

        $query = "
            SELECT enc.*, sde.er_areaid, sde.op_areaid from smed_encounter enc
            INNER JOIN smed_dept_encounter sde
                ON sde.encounter_no = enc.encounter_no
            INNER JOIN smed_area_catalog sac
                ON sde.er_areaid = sac.area_id
            LEFT JOIN smed_batch_order_note sbon
                ON sbon.encounter_no = enc.encounter_no
            LEFT JOIN smed_referral_order sro
                ON sro.order_batchid = sbon.id
                AND sbon.is_finalized = 1
            WHERE
                $condition
                $_sql
            GROUP BY enc.encounter_no
            ORDER BY enc.encounter_date DESC
        ";
        $pdo = DB::getPDO();
        $stm = $pdo->prepare($query);

        $stm->execute();
        return $stm;
    }
}
