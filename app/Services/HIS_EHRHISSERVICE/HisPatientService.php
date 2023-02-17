<?php


namespace App\Services\HIS_EHRHISSERVICE;


use App\Exceptions\EhrException\EhrException;
use App\Models\Encounter;
use App\Models\HIS\HisPerson;
use App\Models\NurseNotes;
use App\Models\NurseNotesBatch;
use App\Models\TracerNurseNotes;
use App\Services\Doctor\PMH\PastMedicalHistoryService;
use App\Services\Doctor\VitalSign\PreAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HisPatientService
{
    public function getAllResults($encounter){
        $pdo = DB::connection('his_mysql');
        $rows = $pdo->select("
                    SELECT 
                        * 
                    FROM
                        (SELECT 
                        r_serv.refno,
                        r_services.name AS service_name,
                        r_serv.pid,
                        r.status,
                        r_services.in_pacs,
                        IF((r_services.pacs_code IS NULL),r_services.service_code, r_services.pacs_code) AS pacs_code,
                        shl7.filename,
                        r.`is_served`,
                        'RAD' AS ref_source,
                        MAX(shl7.date_update) AS date_update,
                        shl7.pacs_order_no,
                        '' AS lis_order_no,
                        r.batch_nr AS batch_nr_grp,
                        r_serv.request_date ,
                        r.`service_date`
                        FROM
                        care_test_request_radio AS r 
                        INNER JOIN seg_radio_serv AS r_serv 
                            ON r.refno = r_serv.refno 
                        INNER JOIN seg_radio_services AS r_services 
                            ON r.service_code = r_services.service_code 
                        LEFT JOIN seg_hl7_radio_tracker AS shl7_t 
                            ON shl7_t.batch_nr = r.batch_nr 
                        LEFT JOIN seg_hl7_radio_msg_receipt AS shl7 
                            ON shl7.pacs_order_no = shl7_t.pacs_order_no  
                        WHERE r_serv.status NOT IN (
                            'deleted',
                            'hidden',
                            'inactive',
                            'void'
                        ) 
                        AND r.status NOT IN (
                            'deleted',
                            'hidden',
                            'inactive',
                            'void'
                        ) 
                        #AND r_serv.fromdept ='RD'
                        AND r_serv.`encounter_nr` = :enr2 
                        #ORDER BY r_serv.request_date DESC 
                        GROUP BY r_serv.`refno`, r_services.`service_code`
                        UNION
                        SELECT 
                        sls.`refno`,
                        slss.`name` AS service_name,
                        ce.`pid`,
                        slserv.status,
                        '' as in_pacs,
                        '' as pacs_code,
                        h.`filename`,
                        sls.`is_served`,
                        slserv.`ref_source`,
                        h.`date_update`,
                        ''  as pacs_order_no,
                        IF(
                            o.lis_order_no IS NOT NULL,
                            o.lis_order_no,
                            tr.lis_order_no
                        ) AS lis_order_no,
                        '' AS batch_nr_grp,
                        sls.create_dt AS request_date ,
                        '' AS service_date
                        FROM
                        seg_lab_servdetails sls 
                        INNER JOIN seg_lab_services slss 
                            ON slss.`service_code` = sls.`service_code` 
                        INNER JOIN seg_lab_serv slserv 
                            ON slserv.`refno` = sls.`refno` 
                        INNER JOIN `care_encounter` ce 
                            ON ce.`encounter_nr` = slserv.`encounter_nr` 
                        LEFT JOIN seg_hl7_lab_tracker tr 
                            ON tr.refno = sls.`refno` 
                        LEFT JOIN seg_lab_hclab_orderno o 
                            ON o.refno = sls.`refno` 
                        LEFT JOIN seg_hl7_hclab_msg_receipt h 
                            ON h.lis_order_no = o.lis_order_no 
                        WHERE slserv.`encounter_nr` = :enr
                        AND slserv.STATUS NOT IN (
                            'deleted',
                            'hidden',
                            'inactive',
                            'void'
                        ) 
                        AND sls.STATUS NOT IN (
                            'deleted',
                            'hidden',
                            'inactive',
                            'void'
                        )
                        GROUP BY sls.`refno`, sls.`service_code`
                        ) tbl 
                    ORDER BY tbl.request_date DESC 
            ", array(
                'enr' => $encounter,
                'enr2' => $encounter,
            ));

        return array(
            'status' => true,
            'data' => $rows,
            // 'err_msg' => $res === false ? $this->getError() : ''
        );
    }


    public function getThisPatient($pid){
        if($this->_is_person_exist($pid)){
            return array(
                'status' => true,
                'pid' =>$pid,
                'data' => array(
                    'person_data' => $this->_getPersonData(null,$pid),
                )
            );
        }
        else
            return array(
                'status' => false,
                'msg' => "Person does not exist."
        );
    }

    private function _is_person_exist($pid){
        $res = HisPerson::query()->find($pid);
        return is_null($res) ? false : true;
    }


    private function _getPersonData($personel_id = null, $pid = null){

        if(isset($personel_id)){
            $condition = "inner join care_personell cpp on cp.pid = cpp.pid
                WHERE cpp.nr = :nr ";
            $value = array('nr' => $personel_id);
        }
        else{
            $condition = "WHERE cp.pid = :pid ";
            $value = array('pid' => $pid);
        }

        // $res = HisPerson::query()
        //     ->select()
        //     ->first();
        $pdo = DB::connection('his_mysql');
        $res = $pdo->select("
            select 
            cp.pid, 
            cp.name_first as firstName, 
            cp.name_middle as middleName, 
            cp.name_last as lastName,
            cp.suffix, 
            cp.sex as gender,
            cp.civil_status,
            cp.cellphone_1_nr,
            cp.create_id,
            cp.date_birth as dateOfBirth,
            cp.place_birth,
            cp.name_search,
            cp.email,
            cp.soundex_namefirst, 
            cp.soundex_namelast, 
            cp.create_id, 
            cp.create_time as create_dt,
            cp.modify_time as modify_dt,
            cp.street_name,
            provinceName.`prov_name`,
            seg_b.brgy_name, 
            seg_m.`mun_name`, 
            seg_m.`zipcode`,
            sr.`religion_name`,
            occ.`occupation_name`,
            scoun.`citizenship`
            from care_person cp
            LEFT JOIN `seg_barangays` AS seg_b
                ON cp.`brgy_nr` = seg_b.`brgy_nr`
            LEFT JOIN `seg_municity` AS seg_m
                ON cp.`mun_nr` = seg_m.`mun_nr`
            LEFT JOIN `seg_religion` sr
                ON cp.`religion` = sr.`religion_nr`
            LEFT JOIN `seg_occupation` occ
                ON cp.`occupation` = occ.`occupation_nr`
            LEFT JOIN `seg_country` scoun
                ON cp.`citizenship`=scoun.`country_code`
            LEFT JOIN (
                SELECT seg_p.`prov_name`, segm.`mun_nr` FROM `seg_municity` segm
                    INNER JOIN `seg_provinces` seg_p
                        ON segm.`prov_nr`=seg_p.`prov_nr`
            ) AS provinceName
            ON provinceName.`mun_nr` = seg_m.`mun_nr`
            {$condition}
        ", $value)
        ;
        
        $resp = collect($res)->recursive()->first();
        return $resp ? $resp->toArray() : [];
    }
}