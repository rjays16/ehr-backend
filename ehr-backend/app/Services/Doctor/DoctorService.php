<?php


namespace App\Services\Doctor;

use App\Exceptions\EhrException\EhrException;
use App\Models\AreaCatalog;
use App\Models\Config;
use App\Models\DeptEncounter;
use App\Models\Encounter;
use App\Models\PersonnelPermission;
use Illuminate\Support\Str;
use App\Models\FavoritePatient;
use App\Models\PersonCatalog;
use App\Models\PersonnelCatalog;
use App\Services\Doctor\Permission\PermissionService;
use App\Services\Patient\PatientService;
use App\Services\Personnel\PersonnelService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PDO;


class DoctorService extends PatientService
{
    /**
     * @var PersonnelCatalog
     */
    private $pesonnel;
    private $patientType;
    private $person_search;

    function __construct(PersonnelCatalog $personnel)
    {
        $this->pesonnel = $personnel;
    }

    public static function init($personnel_id)
    {
      $personnel = PersonnelCatalog::query()->find($personnel_id);
      if(!$personnel)
        throw new EhrException('Personnel does not exist.');

      return new DoctorService($personnel);
    }


    /**
     * @return array 
     * list of patients
     */
    public function getTaggedPatients()
    {
      $patients = [];

      $patients = FavoritePatient::query()
          ->select(
            "enc.encounter_no",
            "enc.spin as pid",
            "enc.encounter_date",
            "name_first",
            "name_last",
            "name_middle",
            "suffix",
            "gender",
            "birth_date",
            "enc.admit_diagnosis2",
            "enc.discharge_dt",
            "enc.discharge_id",
            "enc.is_discharged",
            "enc.parent_encounter_nr",
            "doc.doctor_id",
            "doc.role_id",
            "deptEnc.deptenc_code",
            "deptEnc.er_areaid",
            "area.area_id",
            "area.area_code",
            "area.area_desc")
          ->leftJoin('smed_encounter_doctor as doc','smed_favorite_patient.encounter_no','=','doc.encounter_no')
          ->leftJoin("smed_encounter as enc",'smed_favorite_patient.encounter_no','=','enc.encounter_no')
          ->join('smed_dept_encounter as deptEnc','deptEnc.encounter_no','=','enc.encounter_no')
          ->join('smed_area_catalog as area','area.area_id','=','deptEnc.er_areaid')
          ->join('smed_patient_catalog as p','p.spin','=','enc.spin')
          ->join('smed_person_catalog as person','person.pid','=','p.pid')
          ->where('smed_favorite_patient.doctor_id', $this->pesonnel->personnel_id)
          ->groupBy(['smed_favorite_patient.encounter_no'])
          ->get()
          ;
      $p = new PersonCatalog();
      return $patients->map(function($item) use ($p){
        $item['age'] =  $p->getEstimatedAge(null, $item['birth_date']);
        return $item;
      });
    }

    /**
     * Tag or Untag patient to doctor
     * as doctors favorites
     * @var string $encounter
     */
    public function favoritePatient($encounter)
    {

      $enc = Encounter::query()->find($encounter);
      if(!$enc)
        throw new EhrException('Encounter does not exist.');

      $model = FavoritePatient::query()
              ->where('doctor_id',$this->pesonnel->personnel_id)
              ->where('encounter_no',$encounter)
              ->first();

      if(!$model){
        $model = new FavoritePatient();
        $model->id = (string) Str::uuid();
        $model->encounter_no = $encounter;
        $model->doctor_id = $this->pesonnel->personnel_id;
        if (!$model->save()) {
            throw new EhrException('Failed to save favorite patient');
        }
        return 'Add to favorites.';
      }
      else{
        if (!$model->forceDelete()) {
            throw new EhrException('Failed to remove favorite patient');
        }

        return 'Removed from favorites.';
      }

    }

    public function getPatientLists($data){

        $this->person_search = $data['person_search'];
        $this->patientType = $data['patient_type'];

        if(PermissionService::getAllEhrPermissions()->whereIn('id', PermissionService::permissionWithView())->first() == null)
          return [];

        $hasViewAllPerm =  PermissionService::getAllEhrPermissions()->whereIn('id', PermissionService::permissionWithViewAll())->first() != null;
          
        $getAreaID = auth()->user()->personnel;

        $current_area_id = $getAreaID->currentAssignment->area_id;
        $areaCatalog = $getAreaID->currentAssignment->area;
        $dept_id = $areaCatalog->dept_id;


        $limit = $data['limit'] ? $data['limit'] : 20;
        $date_time = "";

        if ($data['encounter_start_date'] && $data['encounter_end_date']) {
            $date_time = "AND (se.encounter_date >= '" . $data['encounter_start_date'] . "' AND se.encounter_date <= '" . $data['encounter_end_date']."')";
        }

        $is_dept = $dept_id == "0" ? "" : "AND sac.dept_id = $dept_id";
        $dept_code = $current_area_id == $dept_id ? "" : "sro.dept_id = $current_area_id OR sac.area_id = $current_area_id";
        $_sql = "AND ($dept_code $is_dept)";
        if ($current_area_id == $dept_id) {
            $area_id_one = $areaCatalog->depts;
            $area = [];
            $dept = [];


            foreach ($area_id_one as $area_one) {
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

            $area_catalogs = array_merge(array_unique($area), array_unique($dept));
            $area_code = '';
            foreach (array_unique($area_catalogs) as $i => $area_catalog) {
                $or = $i == 0 ? "" : " OR ";
                $dept_code .= $or." sro.dept_id = $area_catalog ";
                $area_code .= $or." sac.area_id = $area_catalog ";
            }
            $_sql = "AND ($dept_code OR $area_code)";

        }
        
        $_patientType = $this->patientType != "ALL" ? "AND sde.deptenc_code = '$this->patientType'" : "";
        $search = explode(",", $this->person_search);
        $is_auth = $hasViewAllPerm ? '':"$_sql";
        $is_encounter = "";
        $is_spin = "";
        $is_name = "";
        $dummy_search = 0;

        if ($this->person_search != "") {
          if (strlen($this->person_search) >= 10 && is_numeric($this->person_search)) {
            $is_encounter = "AND (se0.encounter_no = $this->person_search)";
          } else {
            if (is_numeric($this->person_search)) {
                $is_spin = "AND (se0.spin = $this->person_search)";
            } else {
                $l_name = trim($search[0])."%";
                $is_name = "AND (spa.name_last LIKE '$l_name' OR spa.name_first LIKE '$l_name')";
                if (!empty($search[1])) {
                    $f_name = trim($search[1])."%";
                    $is_name .= " AND (spa.name_first LIKE '$f_name' OR spa.name_last LIKE '$f_name')";
                } 
            }
          }
        }
        $p = new PersonCatalog();
        $pdo = DB::getPDO();
        $query = "SELECT DISTINCT 
                  se.encounter_no,
                  spa.pid,
                  se.encounter_date,
                  spa.name_first,
                  spa.name_last,
                  spa.name_middle,
                  spa.suffix,
                  spa.gender,
                  spa.birth_date,
                  se.admit_diagnosis2,
                  se.discharge_dt,
                  se.discharge_id,
                  se.is_discharged,
                  se.parent_encounter_nr,
                  sed.doctor_id,
                  sed.role_id,
                  sde.deptenc_code,
                  sde.er_areaid,
                  sac.area_id,
                  sac.area_code,
                  sac.area_desc,
                  IFNULL((SELECT
                        sfp.id
                      FROM
                        `smed_favorite_patient` sfp WHERE sfp.`encounter_no` = se.`encounter_no`
                        AND sfp.`doctor_id` = {$data['personnel_id']}
                      LIMIT 1),NULL) AS is_favorite
                FROM
                  (SELECT 
                    se0.* 
                  FROM
                    smed_encounter se0 
                  WHERE (
                      se0.is_cancel IS NULL 
                      OR se0.is_cancel = 0
                    ) 
                    {$is_encounter} {$is_spin}
                ORDER BY se0.encounter_date DESC) se 
                INNER JOIN smed_person_catalog spa 
                  ON se.spin = spa.pid 
                LEFT JOIN smed_batch_order_note sbon 
                  ON sbon.encounter_no = se.encounter_no 
                LEFT JOIN smed_referral_order sro 
                  ON sro.order_batchid = sbon.id 
                  AND sbon.is_finalized = 1
                LEFT JOIN smed_encounter_doctor sed 
                  ON sed.encounter_no = se.encounter_no 
                LEFT JOIN smed_dept_encounter sde 
                  ON sde.encounter_no = se.encounter_no 
                LEFT JOIN smed_area_catalog sac 
                  ON sde.er_areaid = sac.area_id 
                WHERE (
                  sed.is_deleted IS NULL 
                  OR sed.is_deleted = 0
                ) {$_patientType} {$is_auth} {$is_name} {$date_time}
                LIMIT {$limit}";
        $stm = $pdo->prepare($query);
        $stm->execute();
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
        $rows = collect($rows)->map(function($item) use ($p){
            $item['age'] =  $p->getEstimatedAge(null, $item['birth_date']);
            $item['birth_date'] =  strtotime($item['birth_date']);
            $item['encounter_date'] =  strtotime($item['encounter_date']);
            return $item;
        })->toArray();

        //check if from barcode request
        if($data['isBarcode'] && count($rows) > 0){
            if($rows[0]['deptenc_code'] == DeptEncounter::TYPE_OPE || $rows[0]['deptenc_code'] == strtoupper(DeptEncounter::TYPE_OPE)){
                throw new EhrException(DeptEncounter::TYPE_OPD.' is not supported.');
            }
        }

        return $rows;
    }

    public static function config()
    {
        return [
            'm-patient-list' => [
                'list-view' => [
                    'role_name' => [
                    ],
                    'other-permissions' => []
                ],
                'list-search' => [
                    'role_name' => [
                    ],
                    'other-permissions' => []
                ],
                'default-options' => [
                    'encounter-types' => [
                      'ope' => 'OPD',
                      'ipe' => 'IPD',
                      'ere' => 'ER',
                    ],
                    'other-encounter-types' => [
                      'phs' => 'PHS-OUTPATIENT',
                    ],
                    'encounter-types-color' => [
                      'ope' => '#3A5AB2',
                      'phs' => '#3A5AB2',
                      'ipe' => '#44BB44',
                      'ere' => '#F95F5F',
                      'discharged' => '#686868',
                    ],
                ]
            ]
        ];
    }

}