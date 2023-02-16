<?php
namespace App\Exceptions\His;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


use App\Exceptions\Pest\Pest;
use App\Services\HIS_EHRHISSERVICE\HisPatientService;
use App\Services\Soap\HisSoapService;
class HisActiveResource
{

    /**
     * @var HisActiveResource
     */
    private static $inst;
    private $url = '';
    private $pest = null;
    private $header = null;
    private $error;
    private $_cookies = [];


    /**
     * Call this method to get singleton
     *
     * @return HisActiveResource
     */
    public static function instance()
    {
        if (self::$inst == null) {
            self::$inst = new HisActiveResource(
                    env('HIS_URL'), env('HIS_TOKEN')
            );
        }

        return self::$inst;
    }

    public function getCookies()
    {
        return $this->_cookies;
    }

    private function __construct($url, $token)
    {
        $this->pest = new Pest($url);
        $this->_addCookie('TOKEN', $token);
        $this->_addCookie('PERSONEL', $this->_getPersonelID());
        $this->_addCookie('UNAME', $this->_getPersonelUname());
        $this->_addCookie('REQ', 'EHR');
    }

    private function _getPersonelID()
    {
        if (isset(auth()->user()->personnel)) {
            return auth()->user()->personnel->personnel_id;
        } else {
            return "";
        }
    }

    private function _getPersonelUname()
    {
        if (isset(auth()->user()->username)) {
            return auth()->user()->username;
        } else {
            return "";
        }
    }

    public function setPersonellId($id)
    {
        $this->_addCookie('PERSONEL', $id);
    }

    public function setPersonellUname($uname)
    {
        $this->_addCookie('UNAME', $uname);
    }


    private function _addCookie($name, $value)
    {
        $this->_cookies[$name] = $value;
    }

    private function _getheader()
    {
        $cookies = "Cookie: ";
        foreach ($this->_cookies as $key => $value) {
            $cookies .= "{$key}={$value};";
        }

        return [$cookies];
    }

    private function _get($url, $get)
    {
        try{
            return $this->pest->get($url, $get, $this->_getheader());
        }catch (\Pest_ClientError $e){
            return json_encode([
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'full_resp' => $this->getResponseData(),
                'trace' => $e->getFile() . " ({$e->getLine()})"
            ]);

        }catch (\Exception $e){
            return json_encode([
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'full_resp' => $this->getResponseData(),
                'trace' => $e->getFile() . " ({$e->getLine()})"
            ]);
        }
    }

    private function _post($url, $post)
    {
        try{
            return $this->pest->post($url, $post, $this->_getheader());
        }catch (\Pest_ClientError $e){
            return json_encode([
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'full_resp' => $this->getResponseData(),
                'trace' => $e->getFile() . " ({$e->getLine()})"
            ]);
        }catch (\Exception $e){
            return json_encode([
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'full_resp' => $this->getResponseData(),
                'trace' => $e->getFile() . " ({$e->getLine()})"
            ]);
        }
    }

    public function getResponseData()
    {
        return $this->pest->last_response;
    }

    public function onSOAPupdated($encounter_no, $soapData)
    {
        $result = $this->_post(
                "/ehr-req/on/soap/updated",
                [
                        'enc_no' => $encounter_no,
                        'soap'   => $soapData,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }


    public function onSaveSOADiagnosisICD($encounter_no, $soapData)
    {
        
        $soap_obj   = new HisSoapService();
        $p_ID       = $this->_getPersonelID();
        $p_Usr      = $this->_getPersonelUname();
        
        $tmpdata    = $soap_obj->soapICDSave($encounter_no, $soapData, $p_ID, $p_Usr);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getRadioPacsResultUrl($refno)
    {
        $result = $this->_post(
                "/get/radio/pacs/url",
                [
                        'refno' => $refno,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function onSaveSoapClinicalImpression($data)
    {
        $soap_obj     = new HisSoapService();
        $p_ID         = $this->_getPersonelID();     
        $p_Usr        = $this->_getPersonelUname();  
        $d_enNo       = $data['enc_no'];             
        $d_costcenter = $data['from_costcenter'];  
        $d_soap       = $data['soap'];

        $tmpdata = $soap_obj->soapClinicalImpressionSave($d_enNo, $d_soap, $p_ID, $p_Usr, $d_costcenter);

        return empty($tmpdata) ? false : $tmpdata;
    }


    public function onSaveSOAPDiagnosisupdated($encounter_no, $soapData)
    {

        $soap_obj     = new HisSoapService();
        $p_ID         = $this->_getPersonelID();     
        $p_Usr        = $this->_getPersonelUname();  

        $tmpdata = $soap_obj->serviceOnSaveSOAPDiagnosisupdated($encounter_no, $soapData, $p_Usr);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function onSaveSOAPSubjectiveObjectivePlan($encounter_no, $soapData)
    {

        $soap_obj     = new HisSoapService();
        $p_ID         = $this->_getPersonelID();     
        $p_Usr        = $this->_getPersonelUname();  

        $tmpdata = $soap_obj->soapSubjectivePlanObjectiveSave($encounter_no, $soapData, $p_Usr, $p_ID);

        return empty($tmpdata) ? false : $tmpdata;
    }

    // Added by JJ Minor 3/14/2019
    public function onLaboratoryOrder($orders)
    {
        $result = $this->_post(
                "/ehr-req/on/laboratory/updated",
                [
                        'orders' => $orders,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getTypeCharge(){

        $result = $this->_post(
            "/get/typecharge/data",
            []
    );

    $tmpdata = json_decode($result, true);

    return empty($tmpdata) ? false : $tmpdata;
    }


    public function getPersonelData($personelid, $personelUname = '')
    {   

        $result = $this->_post(
                "/get/personnel/all/data",
                [
                        'personel_id' => $personelid,
                    'personel_uname' => $personelUname,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getPersonData($pid)
    {
        $result = $this->_post(
                "/get/persondata/all/data",
                [
                        'pid' => $pid,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getPersonBasicInformation($pid)
    {
        $patienSer = new HisPatientService();
        return $patienSer->getThisPatient($pid);
    }

    public function getUserData($personelid)
    {
        $result = $this->_post(
                "/get/user/all/data",
                [
                        'personel_id' => $personelid,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getSoapDiagAuditTrail($enc)
    {   
        
        $soap_obj = new HisSoapService();

        $tmpdata = $soap_obj->getDiagAuditTrail($enc);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getInsuranceNo($enc)
    {
        $result = $this->_post(
                "/get/insurance/all/data",
                [
                        'encounter' => $enc,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getCaseRateCode($enc)
    {
        $result = $this->_post(
                "/get/code/all/data",
                [
                        'encounter' => $enc,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function onSaveVitalSign($data)
    {
        $result = $this->_post("/save/patient/vital/sign", $data);

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function onRemoveVitalSign($data)
    {
        $result = $this->_post("/remove/patient/vital/sign", $data);

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getAllPatientResults($encounter)
    {
        $patientSrv = new HisPatientService();
        return $patientSrv->getAllResults($encounter);
    }


    public function onSaveDischargeIns($discharge_order)
    {
        $result = $this->_post(
                "/save/discharge/all/data",
                [
                        'discharge_order' => $discharge_order,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getDischargeDiagnosis($encounter_nr)
    {
        $result = $this->_post(
                "/get/dischargediag/all/data",
                [
                        'encounter_nr' => $encounter_nr,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getOutsideMeds($encounter_nr)
    {
        $result = $this->_post(
                "/get/outsidemeds/all/data",
                [
                        'encounter_nr' => $encounter_nr,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }


    public function getInsideMedicine($encounter_nr)
    {
        $result = $this->_post(
                "/get/insidemedicine/all/data",
                [
                        'encounter_nr' => $encounter_nr,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getInsideMeds($gen_code)
    {
        $result = $this->_post(
                "/get/insidemeds/all/data",
                [
                        'gen_code' => $gen_code,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function deleteLaboratory($data)
    {
        $result = $this->_post(
                "/remove/lab/all/data",
                [
                        'data' => $data,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function deleteRadiology($data)
    {
        $result = $this->_post(
                "/remove/rad/all/data",
                [
                        'data' => $data,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function onSaveReferral($data)
    {
        $result = $this->_post(
                "/save/referral/all/data",
                [
                        'data' => $data,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getFinalBillStatus($encounter_nr)
    {
        $result = $this->_post(
                "/get/finalbillstatus/all/data",
                [
                        'encounter_nr' => $encounter_nr,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }


    public function getBillingDate($enc)
    {
        $result = $this->_post(
                "/get/billing_date/all/data",
                [
                        'encounter' => $enc,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }


    public function getFirstCaseRateCode($enc)
    {
        $result = $this->_post(
                "/get/first_code/all/data",
                [
                        'encounter' => $enc,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getSecondCaseRateCode($enc)
    {
        $result = $this->_post(
                "/get/second_code/all/data",
                [
                        'encounter' => $enc,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getEncounterDetails($encounter_nr)
    {
        $result = $this->_post(
                "/get/encounter/details/all/data",
                [
                        'encounter_nr' => $encounter_nr,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getUserAccountDetails($personnel_id)
    {
        $result = $this->_post(
                "/get/useraccount/details/all/data",
                [
                        'personnel_id' => $personnel_id,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }
    public function onSaveFreqRoute($data)
    {
        $result = $this->_post(
                "/save/meds/all/data",
                [
                        'data' => $data,
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }
    public function getHISInsideMedicine($encounter_nr, $parent_encounter, $final_bill)
    {
        $result = $this->_post(
                "/get/hisinsidemedicine/all/data",
                [
                        'encounter_nr' => $encounter_nr,
                        'parent_encounter' => $parent_encounter,
                        'is_final' => $final_bill
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getHISRefItem($refno, $bestellnum)
    {
        $result = $this->_post(
                "/get/hisrefitem/all/data",
                [
                        'refno' => $refno,
                        'bestellnum' => $bestellnum
                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

    public function getPharmaItemsCF4($ref, $bestellnum)
    {
        $result = $this->_post(
                "/get/getpharmaitemscf4/all/data",
                [
                        'refno' => $ref,
                        'bestellnum' => $bestellnum

                ]
        );

        $tmpdata = json_decode($result, true);

        return empty($tmpdata) ? false : $tmpdata;
    }

}