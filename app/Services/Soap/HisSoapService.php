<?php
/**
 * Created by : Marlon Hario.
 * Date: 12/9/2019
 * Time: 9:05 PM
 */

namespace App\Services\Soap;

use App\Exceptions\EhrException\EhrException;
use App\Models\HIS\HisDoctorsDiagnosis;
use App\Models\HIS\HisEncounter;
use App\Models\HIS\HisClinicalImpression;
use App\Models\HIS\HisSoaDiagnosis; 
use App\Models\HIS\HisSoaDiagnosisNew; 
use App\Models\HIS\HisDoctorsNotes; 
use App\Models\HIS\HisPersonnel; 
use App\Models\HIS\HisAuditDiagnosis; 

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HisSoapService
{

    // Check if data exist in doctors diagnosis table 
    // if exist and $is_deleted argument value is 1 
    // then delete existing data to avoide duplicate entry and return true 
    public function isDocDiagExist($icd_code, $personel_nr, $encounter, $is_deleted = 0){

        $reslt = HisDoctorsDiagnosis::query()->where([
            ['encounter_nr', $encounter],
            ['personell_nr', $personel_nr],
            ['icd_code', $icd_code]
        ])->first();
        
        if ($is_deleted == 0) {
            return $reslt ? true : false;
        } else {
            if($reslt)
                $reslt->forceDelete();
            return true;
        }

    }


    // If $soapData argument value is null return success equal to false otherwise loop the data,
    // Proceed to isDocDiagExist function if return false create new data else do nothing
    public function soapICDSave($encounter_no, $soapData, $p_id, $p_user)
    {

        $soapD = $soapData['clinical_imp']['icds'];
        $status = true;
        $msg = '';
        if(!is_null($soapD)){
            foreach ($soapD as $key => $entry){  
                $f = $this->isDocDiagExist($entry['icd_code'], $p_id, $encounter_no, $entry['is_deleted']); 
              
                if($f === false){ 
                    
                    $response = new HisDoctorsDiagnosis();
                    $response->icd_code = $entry['icd_code'];
                    $response->personell_nr = $p_id;
                    $response->encounter_nr = $encounter_no;
                    $response->create_id = $p_user;
                    $response->create_time = Carbon::now()->toDateTimeString();
                    $res = $response->save();

                    if(!$res){  
                        $status = false;
                        $msg = "EHR->HIS Failed to save SOAP Assessment ICD[{$entry['icd_code']}].";
                        break;
                    }
                }
            }
        }


        return !$status ? array(
            'status' => false,
            'success' => false,
            'msg' => $msg
        ) : array(
            'status' => true,
            'success' => true, 
            'msg' => 'Successfully saved ICDs.'
        );
    }

    // Saving Clinical Impression
    public function soapClinicalImpressionSave($d_enNo, $d_soap, $p_ID, $p_Usr, $d_costcenter)
    {   
        // check if encounter_nr exist in clinical_impression table
        $check_clinical = HisClinicalImpression::where('encounter_nr', $d_enNo)->first(); 
        // check if encounter_nr exist in encounter table  
        $check_EROPD = HisEncounter::where('encounter_nr', $d_enNo)->first();
        $skipSaving = false;

        if($d_costcenter && $check_EROPD->er_opd_diagnosis){
            $skipSaving = true;
        }

        // if $scapeSaving is true escape the following transactions and return true for the response,
        // otherwise update encounter entry data if encounter_type not equal to 3
        // if variable $check_clinical has return data update the clicnical empression base on encounter_nr given
        //  else create new data entry
        if(!$skipSaving){

            if($check_EROPD->encounter_type != 3){

                $hist_concat  = $check_EROPD->history ."Update Impression ".date('m-d-Y H:i:s').' '.$p_Usr.'\n")';

                $check_EROPD->er_opd_diagnosis = $d_soap['clinical_imp']['clinical_imp']."\n";
                $check_EROPD->history = $hist_concat;
                $check_EROPD->modify_id = $p_Usr;
                $check_EROPD->modify_time = date('m-d-Y H:i:s'); 
                $res = $check_EROPD->save();


                if(!$res){
                    return array(
                        'result' => $res,
                        'success' => false, 
                        'status' => false, 
                        'msg' => 'Diagnosis was not updated.', 
                    );
                }
            }

            
            if($check_clinical){

                $check_clinical->history = $check_clinical->history. ("Impression: {$d_soap['clinical_imp']['clinical_imp']} Update Impression ".date('m-d-Y H:i:s'). " {$p_Usr}\n");
                $check_clinical->clinical_impression = $d_soap['clinical_imp']['clinical_imp']; 
                $res = $check_clinical->save();

            } else {

                $post =  new HisClinicalImpression();
                $post->encounter_nr = $d_enNo;
                $post->clinical_impression = $d_soap['clinical_imp']['clinical_imp'];
                $post->history = "Impression: {$d_soap['clinical_imp']['clinical_imp']} Created from [EHR] on ".date('m-d-Y H:i:s'). " by: {$p_Usr}\n";
                $res = $post->save();
                      
            }
        } else { $res = true; }


        return $res ? array(
            'result' => $res, 
            'status' => true,
            'success' => true, 
            'msg' => 'Clinical impression updated.',
        ) : array(
            'result' => $res, 
            'status' => false,
            'success' => false, 
            'msg' => 'Clinical impression was not updated.', 
        );
    }

    // construct history value
    private function  _getsoadiagnosis_history($entry, $soap, $personelUsername){
        $history = '';
             
        if($entry){  

            $hist1  = "Final Diagnosis: {$soap['final_diag']['value']} Updated  from [EHR] on ";
            $hist1 .= Carbon::now()->toDateTimeString()." by: {$personelUsername} ";

            $history .= $entry['final_diagnosis'] != $soap['final_diag']['value'] ?  $hist1 : "";

            $hist2  = ($history != '' ? "\n" : '');
            $hist2  .= "Other Diagnosis: {$soap['other_diag']['value']} Updated  from [EHR] on ";
            $hist2  .= Carbon::now()->toDateTimeString()." by: {$personelUsername} ";

            $history .= $entry['other_diagnosis'] != $soap['other_diag']['value'] ? $hist2 : '';

        } else {

            $hist1  = "Final Diagnosis: {$soap['final_diag']['value']} Created  from [EHR] on ";
            $hist1 .= Carbon::now()->toDateTimeString()." by: {$personelUsername}";

            $history .= trim($soap['final_diag']['value']) != '' ? $hist1 : '';

            $hist2  = ($history != '' ? "\n" : '');
            $hist2 .= "Other Diagnosis: {$soap['other_diag']['value']} Created  from [EHR] on ";
            $hist2 .= Carbon::now()->toDateTimeString()." by: {$personelUsername}";

            $history .= trim($soap['other_diag']['value']) != '' ? $hist2 : '';

        } 
        
        return $history != '' ? $history."\n" : $history;
    }

    // saving SOAP diagnosis
    public  function serviceOnSaveSOAPDiagnosisupdated ($enc_no, $soap, $personelUsername) {
        // Check if encounter exist in soa_diagnosis_new table 
        $checkDocDiagNew = HisSoaDiagnosisNew::query()->where('encounter_nr', $enc_no)->first();
        // Check if encounter exist in soa_diagnosis table 
        $checkDocDiag = HisSoaDiagnosis::query()->where('encounter_nr', $enc_no)->first();
        // Check if encounter exist in encounter table 
        $checkEnc = HisEncounter::query()->where('encounter_nr', $enc_no)->first();
        
        $histValue = $this->_getsoadiagnosis_history($checkDocDiag, $soap, $personelUsername);
        
        $res = '';
        
        // If $checkDocDiag checking is return true proceed to update query else create new data entry
        if($checkDocDiag){
            // If $checkEnc['is_discharged'] is equal to 0 then update existing data else $res set to true
            if(!$checkEnc['is_discharged']){ 
                $checkDocDiag->final_diagnosis = $soap['final_diag']['value'];
                $checkDocDiag->other_diagnosis = $soap['other_diag']['value'];
                $checkDocDiag->modify_date = Carbon::now()->toDateTimeString();
                $checkDocDiag->modify_id = $personelUsername;
                $checkDocDiag->history = $checkDocDiag->history . $histValue;
                
                $res = $checkDocDiag->save(); 

            } else { $res = true; }

        } else {

            $posts = new HisSoaDiagnosis();  
            $posts->encounter_nr = $enc_no;
            $posts->final_diagnosis = $soap['final_diag']['value'];
            $posts->other_diagnosis = $soap['other_diag']['value'];
            $posts->create_date = Carbon::now()->toDateTimeString();
            $posts->create_id = $personelUsername;
            $posts->modify_date = Carbon::now()->toDateTimeString();
            $posts->modify_id = $personelUsername;
            $posts->history = $this->_getsoadiagnosis_history($checkDocDiag, $soap, $personelUsername);
            $res = $posts->save(); 
        }

        if($res){
            // If $checkDocDiagNew has data then update data else create new data
            if($checkDocDiagNew){

                $checkDocDiagNew->final_diagnosis = $soap['final_diag']['value'];
                $checkDocDiagNew->other_diagnosis = $soap['other_diag']['value'];
                $checkDocDiagNew->modify_date = Carbon::now()->toDateTimeString();
                $checkDocDiagNew->modify_id = $personelUsername;
                $checkDocDiagNew->history = $checkDocDiag->history . $histValue;
                $res = $checkDocDiagNew->save(); 

            } else {

                $posts = new HisSoaDiagnosisNew();  
                $posts->encounter_nr = $enc_no;
                $posts->final_diagnosis = $soap['final_diag']['value'];
                $posts->other_diagnosis = $soap['other_diag']['value'];
                $posts->create_date = Carbon::now()->toDateTimeString();
                $posts->create_id = $personelUsername;
                $posts->modify_date = Carbon::now()->toDateTimeString();
                $posts->modify_id = $personelUsername;
                $posts->history = $this->_getsoadiagnosis_history($checkDocDiag, $soap, $personelUsername);
                $res = $posts->save(); 

            }

            return $res ? array(
                'status' => true,
                'success' => true,
                'result' => $res, 
                'msg' => 'Successfully saved'
            ) : array(
                'status' => false,
                'success' => false, 
                'result' => $res,
                'msg' => 'Failed to save final or other diagnosis(2).'
            );
        }

        return array(
            'status' => false,
            'success' => false,
            'result' => $res,
            'msg' => 'Failed to save final or other diagnosis(1).'
        );
    }


    // Saving SOAP subjective
    public function soapSubjectivePlanObjectiveSave($encounter_no, $soapData, $p_Usr, $p_ID){
        $res = ''; $data = array(); $query = '';
        // Check if data exist in doctors_notes table with relational to encounters table
        $found = HisDoctorsNotes::with(array('encounters'=>function($query){
                    $query->select('encounter_nr','pid');
                }))->where('encounter_nr', $encounter_no)->first();

        // If $found variable has data update the exxisting data else 
        // create new data
        if($found){ 
            $histCon  = 'CONCAT(history, "';
            $histCon .= 'UPDATE: '.Carbon::now()->toDateTimeString().' EHR['.$p_Usr.']\n")';

            $found->chief_complaint = $soapData['subjective']['names']."\n".$soapData['subjective']['others']['value'];
            $found->physical_examination = $soapData['objective']['value'];
            $found->clinical_summary = $soapData['plan']['value'];
            $found->history = $histCon;
            $res = $found->save();

        } else {

            $histCon  = 'CONCAT(history, "';
            $histCon .= 'CREATE: '.Carbon::now()->toDateTimeString().' EHR['.$p_Usr.']\n")';

            $posts = new HisDoctorsNotes();  
            $posts->encounter_nr = $encounter_no;
            $posts->personell_nr = $p_ID;
            $posts->chief_complaint = $soapData['subjective']['names']."\n".$soapData['subjective']['others']['value'];
            $posts->physical_examination = $soapData['objective']['value'];
            $posts->clinical_summary = $soapData['plan']['value'];
            $posts->history = $histCon;
            $posts->create_id = $p_Usr;
            $posts->create_time = Carbon::now()->toDateTimeString();
            $res = $posts->save();

        }

        return $res ? array(
            'result' => $res,
            'success' => true, 
            'status' =>true, 
            'found' => $found, 
            'msg' => 'Subjective, Objective, Plan saved.'
        ) : array(
            'result' => $res,
            'success' => false, 
            'status' =>false, 
            'found' => $found,
            'err_msg' => 'dasew', 
            'msg' => 'Subjective, Objective, Plan failed saved.'
        );
    }
    
    // Get data for SOAP assessment diagnosis trail
    public function getDiagAuditTrail($encounter){
        // Check if there is data exist
        $res = HisAuditDiagnosis::select([
           '*',
           DB::raw('fn_get_personell_lastname_first_by_loginid(encoder) as doc_name')
        ])->where('encounter_nr', $encounter)->orderByDesc('date_changed')->get();

        $data = array();

        // If found data loop all data and assign into new array with status true otherwise
        // return status false
        if($res->count()){
            foreach ($res as $key => $row){

                $olFDiag = $row["old_final_diagnosis"];
                $olODiag = $row["old_other_diagnosis"];

                $data[] = array(
                    'id' => $row["id"],
                    'encounter_nr' => $row["encounter_nr"],
                    'date_changed' => strtotime($row["date_changed"]),
                    'doctor_name' => $row["doc_name"],
                    'diagnosis' => is_null($olFDiag) || empty($olFDiag) ?  $olODiag : $olFDiag,
                    'tod' => $row["tod"]
                );
            }

            return array(
                'status' => true,
                'data' => $data,
            );

        } else {
            return array(
                'status' => true,
                'data' => $data,
                'msg' => 'No data found'
            );
        }

    }

}