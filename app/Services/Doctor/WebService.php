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


class WebService extends PatientService
{
    /**
     * @var PersonnelCatalog
     */
    private $pesonnel;
    private $patientType;
    private $person_search;

    function __construct()
    {
    }

    public function getPatientListsWebservice($data){

        $this->person_search = $data['person_search'];
        $this->patientType = $data['patient_type'];

        $limit = 20;
        $date_time = "";

        if ($data['start_date']) {
            $date_time = "AND (se0.encounter_date >= '" . $data['start_date'] . "')";
        }

        $_patientType = $this->patientType != "ALL" ? "AND sde.deptenc_code = '$this->patientType'" : "";
        $search = explode(",", $this->person_search);
        $is_encounter = "";
        $is_spin = "";
        $is_name = "";

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
                  sac.area_desc
                FROM
                  (SELECT 
                    se0.* 
                  FROM
                    smed_encounter se0 
                  WHERE (
                      (se0.is_cancel IS NULL 
                      OR se0.is_cancel = 0)
                      {$date_time}
                    ) 
                    {$is_encounter} {$is_spin} 
                ORDER BY se0.encounter_date ASC) se 
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
                ) {$_patientType} {$is_name}
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


        return $rows;
    }



}