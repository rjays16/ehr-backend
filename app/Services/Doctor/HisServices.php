<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 10/5/2019
 * Time: 4:28 PM
 */

namespace App\Services\Doctor;

use App\Exceptions\EhrException\EhrException;
use App\Models\Encounter;
use App\Models\HIS\HisEncounter;
use App\Models\HIS\HisPharmaItemsCf4;
use App\Models\HIS\HisPharmaProductsMain;
use App\Services\Doctor\Permission\PermissionService;
use Illuminate\Support\Facades\DB;

class HisServices
{
    /**
     * @var \Illuminate\Database\ConnectionInterface $hisDb
     */
    public $hisDb;

    /**
     * @var String $encounter
     */
    public $encounterNo;

    /**
     * @var Encounter $encounter
     */
    public $encounter;

    public $bill_date;

    public function __construct($encounterNo, $bill_date = null)
    {
        if($encounterNo instanceof String){
            $this->setEncounter(Encounter::query()->find($encounterNo));
            if(is_null($this->encounter))
                throw new EhrException('HIS Encounter does not exist.');
        }
        else if($encounterNo instanceof HisEncounter){
            $this->setEncounter($encounterNo->ehrEncounter);
        }
        else if($encounterNo instanceof Encounter){
            $this->setEncounter($encounterNo);
        }

        $this->encounterNo = $this->encounter->encounter_no;

        $this->bill_date = $bill_date;

        $this->hisDb =  DB::connection('his_mysql');
    }

    /**
     * @param HisEncounter $encounter
     */
    public function setEncounter($encounter = null)
    {
        $this->encounter = $encounter;
    }

    public function getFinalBill()
    {
        // 'is_final, is_deleted'
        return $this->encounter->hisEncounter->billing;
    }

    public function getCaseRateCode()
    {
        if(!$this->encounter->hisEncounter->billing)
            return [];
        return $this->encounter->hisEncounter->billing->caserate;
    }

    public function getInsuranceNo()
    {
        $sql = "SELECT
                  seim.hcare_id,
                  IF(sei.`remarks` = '1' OR sei.`remarks` IS NULL,
                  seim.`insurance_nr`, siro.`title`) AS insurance_nr,
                  seim.employer_no,
                  seim.employer_name,
                  IF(seim.relation='M','Member',sr.relation_desc) AS relation
                FROM seg_encounter_insurance_memberinfo AS seim
                LEFT JOIN seg_relationtomember AS sr
                  ON seim.relation = sr.relation_code
                INNER JOIN seg_encounter_insurance sei
                  ON sei.`encounter_nr` = seim.`encounter_nr`
                LEFT JOIN seg_insurance_remarks_options siro
                  ON sei.`remarks` = siro.`id`
                WHERE seim.encounter_nr ='{$this->encounterNo}'
                AND seim.hcare_id = 18";

        return collect($this->hisDb->select($sql))->recursive()->toArray();
    }

    public function getBillingDate()
    {
        // $command = $this->hisDb->createCommand();
        // $command->select('bill_dte');
        // $command->from('seg_billing_encounter');
        // $command->where('encounter_nr=:encounter_nr AND (is_deleted IS NULL OR is_deleted=:is_deleted)');
        // $command->params[':encounter_nr'] = $this->encounterNo;
        // $command->params[':is_deleted'] = 0;

        // $details = $command->queryAll();

        return $this->getFinalBill();
    }

    public function getFirstCaseRateCode()
    {
        $billDetails = collect($this->getFinalBill());

        $data = $this->hisDb->select("
            select
            sbc.package_id
            from
            seg_billing_encounter sbe
            left join seg_billing_caserate sbc on sbe.bill_nr = sbc.bill_nr
            where sbc.bill_nr=:bill_nr AND sbc.rate_type=:first_rate_type
        ",[
            'bill_nr' => $billDetails->get('bill_nr'),
            'first_rate_type' => 1
        ]);

        return collect($data)->recursive()->all();
    }

    public function getSecondCaseRateCode()
    {
        $billDetails = collect($this->getFinalBill());

        $data = $this->hisDb->select("
            select
            sbc.package_id
            from
            seg_billing_encounter sbe
            left join seg_billing_caserate sbc on sbe.bill_nr = sbc.bill_nr
            where sbc.bill_nr=:bill_nr AND sbc.rate_type=:first_rate_type
        ",[
            'bill_nr' => $billDetails->get('bill_nr'),
            'first_rate_type'=> 2
        ]);

        return collect($data)->recursive()->all();
    }


    public function getPersonDeath()
    {

        $data = $this->hisDb->selectOne("
            select
                CONCAT(cp.death_date,' ', cp.death_time) as death_date, cp.death_encounter_nr
            from
            care_encounter ce
            left join care_person cp ON ce.pid = cp.pid AND ce.encounter_nr = cp.death_encounter_nr
            where ce.encounter_nr=:encounter_nr
        ",[
            'encounter_nr' => $this->encounterNo,
        ]);

        return collect($data);
    }


    public function getReceiverNotification()
    {
        $encounterType = $this->encounter->hisEncounter->typeEncounter->type;

        if ($encounterType === "ER_Admission") {
            $notifyId = "_a_2_notif_lab_dept_ipd";
        } else if ($encounterType === "ER") {
            $notifyId ="_a_2_notif_lab_dept_er";
        } else if ($encounterType === "IPD_IPBM"){
            $notifyId="_a_2_notif_lab_dept_ipbm_ipd";
        } else {
            $notifyId = '';
        }

        $where = $notifyId ? "WHERE (
            permission LIKE '%_a_1_notif_lab_dept%'  OR  permission LIKE '%_a_0_all%'
            OR permission LIKE '%".$notifyId."%')" : "WHERE (
            permission LIKE '%_a_1_notif_lab_dept%')";

        $sql = "SELECT 
          login_id 
        FROM
          care_users 
          $where
        ";

        $data = $this->hisDb->select($sql);
        return collect($data)->recursive()->map(function($item){
            return $item['login_id'];
        })->all();
    }

}
