<?php

namespace App\Services\HIS\HisPhicCoverage;

use App\Models\Encounter;
use App\Models\HIS\HisEncounter;
use Illuminate\Support\Facades\DB;

class PhicCoverageService
{

    public $encounter;
    public $pdo;
    public $default_pcf;
    public $phic_id;
    public $bsked_id_xlo;
    public $conf_nbb;
    public $bill_nr;
    public $old_billnr;

    /**
     * PhicCoverageService constructor.
     * @param \App\Models\Encounter|null $encounter
     */
    function __construct(Encounter $encounter = null)
    {
        $this->encounter = $encounter;
        $this->pdo = DB::connection('his_mysql');
        $this->phic_id = config('app.phic_id');
        $this->bsked_id_xlo = config('app.bsked_id_xlo');
        $this->conf_nbb = config('app.conf_nbb');
        $this->default_pcf = config('app.default_pcf');
    }

    public function getEncounterDate(){
        return $this->encounter->encounter_date;
    }

    public function hasBillDate(){
        $hisEncounter = HisEncounter::where('encounter_nr', $this->encounter->encounter_no)->with('billing')->first();
        $bill_details = $hisEncounter->billing;
        return $bill_details;
    }

    public function isFinal(){
        $hisEncounter = HisEncounter::where('encounter_nr', $this->encounter->encounter_no)->with('billing')->first();
        $is_final = $hisEncounter->billing ? $hisEncounter->billing->is_final : null;
        $this->bill_nr = $hisEncounter->billing ? $hisEncounter->billing->bill_nr : null;
        return $is_final ? true : false;
    }

    public function getConfinementType(){
        $filter = $this->encounter->parent_encounter_nr ? true : false ;
        if($filter){
            $sql = "SELECT
                      sec.confinetype_id,
                      sec.classify_dte,
                      sec.create_id
                    FROM
                      `seg_encounter_confinement` sec
                    WHERE encounter_nr = {$this->encounter->encounter_no}
                      OR encounter_nr = {$this->encounter->encounter_no}
                      AND is_deleted = 0
                    ORDER BY classify_dte DESC
                    LIMIT 1 ";
        }else{
            $sql = "SELECT
                      sec.confinetype_id,
                      sec.classify_dte,
                      sec.create_id
                    FROM
                      `seg_encounter_confinement` sec
                    WHERE encounter_nr = {$this->encounter->encounter_no}
                      AND is_deleted = 0
                    ORDER BY classify_dte DESC
                    LIMIT 1 ";
        }

        $result = collect($this->pdo->select($sql))->recursive()->first();
        $n_id = $result ?  $result['confinetype_id'] : 0;

        if($n_id===0){
            $confinetype_id = "SELECT
                      stc.confinetype_id
                    FROM
                      `seg_type_confinement` stc
                    WHERE stc.is_default = 1";

            $confinetype_id_result = collect($this->pdo->select($confinetype_id))->recursive()->first();
            $n_id = $confinetype_id_result ? $confinetype_id_result['confinetype_id'] : null;
        }

        return $this->confinetype_id = $n_id;

    }

    public function getDefinedPCF(){
        $sql = "SELECT pcf FROM `seg_hospital_info`";
        return collect($this->pdo->select($sql))->recursive()->first();
    }

    public function setBillArgs($bill_dteto,$bill_dtefrm = null,$death_dt){

        $this->encounter->parent_encounter_nr;
        if($this->encounter->parent_encounter_nr!==''){
            $this->bill_frmdte = $this->getEncounterDate();
        }
        $this->is_final = $this->isFinal();
        $this->iscoverdbypkg = 0;

        $n_id = $this->getConfinementType();

        $ncutoff  = -1;

        $this->cutoff_hrs = $ncutoff;
        $pcf = $this->getDefinedPCF();
        $this->pcf = $pcf ? $this->default_pcf  : $pcf[0];

    }

    public function isPHIC(){
        $ncount = 0;
        $sql = "SELECT
                  COUNT(*) isphic
                FROM
                  seg_encounter_insurance
                WHERE encounter_nr = {$this->encounter->encounter_no}
                  AND hcare_id = {$this->phic_id}
                ORDER BY priority
                LIMIT 1 ";

        return collect($this->pdo->select($sql))->recursive()->first();

    }

    public function hasNBB(){
        $sql = "
                SELECT
                  sem.encounter_nr
                FROM
                  seg_encounter_memcategory sem
                  LEFT JOIN seg_memcategory smc
                    ON sem.memcategory_id = smc.memcategory_id
                  LEFT JOIN seg_encounter_insurance sei
                    ON sem.encounter_nr = sei.encounter_nr
                WHERE sei.hcare_id = {$this->phic_id}
                  AND smc.isnbb = '1'
                  AND sem.encounter_nr = '{$this->encounter->encounter_no}'
                        ";
        return collect($this->pdo->select($sql))->recursive()->first();
    }

    public function hasPaywardAccom(){

        $sql = "SELECT ce.encounter_nr, ce.`current_ward_nr`,cw.accomodation_type
                        FROM care_encounter AS ce
                        INNER JOIN care_ward AS cw ON ce.current_ward_nr = cw.nr
                        WHERE ce.encounter_nr = '{$this->encounter->encounter_no}' AND cw.`accomodation_type` = '2'
                        UNION
                        SELECT sela.encounter_nr, sela.group_nr, cw.accomodation_type
                        FROM seg_encounter_location_addtl AS sela
                        INNER JOIN care_ward AS cw ON sela.group_nr = cw.nr
                        WHERE sela.encounter_nr = '{$this->encounter->encounter_no}' AND cw.`accomodation_type` = '2'
                        AND sela.is_deleted != '1'
                        UNION
                        SELECT sel.encounter_nr, sel.group_nr, cw.accomodation_type
                        FROM care_encounter_location AS sel
                        INNER JOIN care_ward AS cw ON sel.group_nr = cw.nr
                        WHERE sel.encounter_nr = '{$this->encounter->encounter_no}' AND cw.`accomodation_type` = '2'
                        AND sel.is_deleted != '1'
                        ";

        return collect($this->pdo->select($sql))->recursive()->first();

    }



    public function  getEncounterLimit(){
        $limit = 1 ;

        if($this->hasNBB() && !$this->hasPaywardAccom()){
            $sql = "SELECT
                      amountlimit
                    FROM
                      seg_hcare_confinetype
                    WHERE bsked_id = '{$this->bsked_id_xlo}'
                      AND confinetype_id = '{$this->conf_nbb}'";
        }else{
            $sql = "SELECT
                      c.amountlimit
                    FROM
                      seg_encounter_confinement AS a
                      LEFT JOIN seg_type_confinement AS b
                        ON a.confinetype_id = b.confinetype_id
                      LEFT JOIN seg_hcare_confinetype AS c
                        ON c.confinetype_id = b.confinetype_id
                    WHERE a.encounter_nr = '{$this->encounter->encounter_no}'
                      AND c.bsked_id = '{$this->bsked_id_xlo}'
                      AND a.is_deleted <> 1
                    ORDER BY a.create_time DESC";
        }

        $xlo = collect($this->pdo->select($sql))->recursive()->first();

        $limit= array('xlo' => $xlo ? $xlo['amountlimit'] : null );
        return $limit;
    }

    public function getDefaultLimit(){

        $sql = "SELECT
                  amountlimit
                FROM
                seg_hcare_confinetype
                WHERE bsked_id= '{$this->bsked_id_xlo}'";

        return collect($this->pdo->select($sql))->recursive()->first();
    }

    public  function  getTotalAdditionalLimit(){
        $sql = "SELECT
                  SUM(`amountxlo`) AS xlo
                FROM
                  seg_additional_limit
                WHERE is_deleted IS NULL
                  AND encounter_nr = '{$this->encounter->encounter_no}'";
        return collect($this->pdo->select($sql))->recursive()->first();
    }

    public function getActualSrvCoverage($phic_id = -1){

        if ($this->old_billnr == '') {
            $srefno = 'T'.$this->encounter->encounter_no;
        }else{
            $srefno = $this->old_billnr;
        }
        $firm_filter = ($phic_id == -1) ? "" : " and hcare_id = '{$this->phic_id}'";

        $strSQL = "select sum(coverage) as totalcoverage
                      from seg_applied_coverage
                      where ref_no = '$srefno' and source <> 'M'".$firm_filter;

        $result = collect($this->pdo->select($strSQL))->recursive()->first();
        $total = (is_null($result) ? 0 : $result['totalcoverage']);

        return $total;

    }

    public function getPersonDeath()
    {
        $sql = "SELECT
                  CONCAT(cp.death_date, ' ', cp.death_time) AS death_date,
                  cp.death_encounter_nr
                FROM
                  care_encounter ce
                  LEFT JOIN care_person cp
                    ON ce.pid = cp.pid
                    AND ce.encounter_nr = cp.death_encounter_nr
                WHERE ce.encounter_nr = '{$this->encounter->encounter_no}'";

        return collect($this->pdo->select($sql))->recursive()->first();

    }

    public function getPhicCoverage(){

        $is_death = $this->getPersonDeath();

        $bill_dt = strftime("%Y-%m-%d %H:%M:%S");
        if($this->hasBillDate()){
            $bill_details = $this->hasBillDate();
            if($bill_details['is_final']== 1){
                $this->setBillArgs($bill_details['bill_dte'],$bill_details['bill_frmdte'],$is_death['death_date']);
            }else{
                $this->setBillArgs($bill_dt,$bill_details['bill_frmdte'],$is_death['death_date']);
            }
        }else{
            $this->setBillArgs($bill_dt, null, $is_death['death_date']);
        }


        if($this->isPHIC()){
            $limit = $this->getEncounterLimit();
            $def_limit = $this->getDefaultLimit();
            $additional = $this->getTotalAdditionalLimit();
            $xlo_covered = $this->getActualSrvCoverage($this->phic_id);

            if($limit){
                if($limit['xlo'] != false) {
                    $xlo = $limit['xlo'] + $additional['xlo'];
                }
                else{
                    $xlo = $def_limit['amountlimit'] + $additional['xlo'];
                }

                $xloCov = $xlo - $xlo_covered;

            }else{
                $xloCov = 0;
            }
        }else{
            $xloCov = NULL;
        }

        return is_null($xloCov) ? 'NULL' :number_format($xloCov,2,".",",");

    }
}
