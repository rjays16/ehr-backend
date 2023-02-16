<?php


namespace App\Services\Doctor;

use App\Exceptions\EhrException\EhrException;
use App\Exceptions\His\HisActiveResource;
use App\Models\BatchOrderNote;
use App\Models\DeptEncounter;
use App\Models\Encounter;
use App\Models\EncounterCourseward;
use App\Models\EndCare;
use App\Models\MedicalHistorySummary;
use App\Models\MenstrualHistory;
use App\Models\PastMedicalHistory;
use App\Models\PersonCatalog;
use App\Models\PhilMedicineForm;
use App\Models\PhilMedicineStrength;
use App\Models\PregnantHistory;
use App\Models\PresentIllness;
use App\Models\ReferralInstitution;
use App\Models\SmedDiagnosisProcedure;
use App\Models\SmedRepetitiveSession;
use App\Services\Doctor\Soap\SoapService;
use App\Services\Doctor\VitalSign\PreAssessmentService;
use App\Utility\JasperReport;
use PHPJasper\PHPJasper;

class CF4Service
{

    /**
     * @var Encounter $_encounter
     */
    public $_encounter;

    function __construct($encounter=null)
    {
        $this->_encounter = $encounter;
    }


    public static function init($encounter)
    {
        $enc = Encounter::query()->find($encounter);
        if(!$enc)
            throw new EhrException('Encounter not found', 404);
        return new CF4Service($enc);
    }



    public function printCf4PDF()
    {

        $his_service = new HisServices($this->_encounter, strftime('%Y-%m-%d %H:%M:%S'));

        $enc = $this->_encounter;
        $final_bill = $his_service->getFinalBill();
        $is_final = 0;

        $peDeatails = $this->getPhysicalExaminationDetails();
        $preassessment = collect($this->getVitalSigns());
        $sign_and_symptoms = $this->getSignsAndSymptoms();
        $is_death = $his_service->getPersonDeath();

        if ($enc['parent_encounter_nr'] && $final_bill['is_final'] == $is_final) {
            $medicine = $this->getMedicine();

            $coursewardInherit = $this->getCourseWardParentEnc();
            $coursewardChild = $this->getCourseWard();
            $courseward = collect($coursewardInherit)->merge($coursewardChild);
        } else {
            $medicine = $this->getMedicine();
            $courseward = $this->getCourseWard();

        }
        

        $chief_complaint = collect($this->getChiefComplaint());


        $date_gravity = 'N/A';
        $date_parity = 'N/A';
        $last_period_menstrual  = 'N/A';

        $insurance_no = $this->getInsuranceNo();
        $case_rate_codes = $this->getCaseRateCode();
        $hci_institution = collect(ReferralInstitution::query()->where('encounter_no' , $this->_encounter->encounter_no)->first());
        $outcome_treatment = collect(EndCare::query()->where('encounter_no',$this->_encounter->encounter_no)->first());
        $ob_gyn_preg = collect(PregnantHistory::query()->where('encounter_no', $this->_encounter->encounter_no)->first());
        $f_code = $s_code = null;
        foreach ($case_rate_codes as $codes) {
            if ($codes['rate_type'] == 1) {
                $f_code = $codes['package_id'];
            }

            if ($codes['rate_type'] == 2) {
                $s_code = $codes['package_id'];
            }
        }

        if ($ob_gyn_preg) {
            $date_gravity = $ob_gyn_preg->get('date_gravidity','');
            $date_parity = $ob_gyn_preg->get('date_parity','');
        }


        $encounter = $this->_encounter;

        $ob_gyn_mens = $this->_encounter->menstrualHistory;

        if ($ob_gyn_mens) {
            if($ob_gyn_mens->last_period_menstrual == '0000-00-00')
                $last_period_menstrual = "";
            else
                $last_period_menstrual = $ob_gyn_mens->last_period_menstrual;
        }
        else{
            $last_period_menstrual = "";
        }
        $medicalHistory = $this->_encounter->medicationSummary;
        $present_illness = $this->_encounter->presentIllnes;
        $person = $encounter->spin0->p;
        $discharge_dt = is_null($final_bill) ? '00-00-0000 00:00 z' : date('m-d-Y h:i a', strtotime($final_bill->bill_dte));
        $admission_dt = $encounter->encounter_date == null ? '00-00-0000 00:00 z' : date('m-d-Y h:i a', strtotime($encounter->encounter_date));


        $logo_path = "/images/phic_logo.png";

        $data = [];
        $meds[] = ['for_header' => 1];
        $data_back = collect($meds)->merge($medicine);
        $data = collect($courseward)->merge($data_back)->toArray();
        $bp = $preassessment->get('systole','') . '/' . $preassessment->get('diastole','');

        $admitting_diagnosis = $chief_complaint['admitting_diagnosis'];

        if(!empty($person->suffix)){
            $person->name_first = (str_replace($person->suffix, "", $person->name_first));
        }

        $dept = $this->_encounter->currentDeptEncounter;
        $encounter_type = strtoupper($dept->deptenc_code);

        if($encounter_type == 'OPE') {
            $repetitive = $this->_encounter->batchOrderNotes;

            $arr_start = [];
            $arr_end = [];
            $counterFirst = 0;
            $counterSecond = 0;
            $phic = $insurance_no[0]['insurance_nr'];

            foreach ($repetitive as $reps) {
                /**
                 * @var BatchOrderNote $reps;
                 */
                $id = $reps->id;
                $repetitiveStart = $reps->repetitiveSessionOrders;

                $first_caseRate = $his_service->getFirstCaseRateCode();
                $second_caseRate = $his_service->getSecondCaseRateCode();

                foreach ($repetitiveStart as $a) {
                    /**
                     * @var SmedRepetitiveSession $a;
                     */
                    $caseRate = collect($a->diagnosisProcedure);

                    array_push(
                            $arr_start,
                            date('m-d-Y', strtotime($a['session_start_date'])).' '.date(
                                    'h:i a',
                                    strtotime($a['session_start_time'])
                            )
                    );
                    array_push(
                            $arr_end,
                            date('m-d-Y', strtotime($a['session_end_date'])).' '.date(
                                    'h:i a',
                                    strtotime($a['session_end_time'])
                            )
                    );

                    $frate = collect();
                    foreach ($first_caseRate as $package_id) {
                        $frate = collect($package_id);
                    }

                    $srate = collect();
                    foreach ($second_caseRate as $package_id) {
                        $srate = collect($package_id);
                    }

                    if($caseRate->get('rvs_code') == $frate->get('package_id')) {
                        $counterFirst++;
                    }

                    if($caseRate->get('rvs_code') == $srate->get('package_id')) {
                        $counterSecond++;
                    }
                }
            }
            
            $start = $arr_start ? min($arr_start) : '';
            $end = $arr_start ? max($arr_start) : '';

            if (empty($start) && empty($end)) {
                $admission_date = $admission_dt;
                $discharge_date = ($is_death->get('death_date','') != '0000-00-00 00:00:00' && $is_death->get('death_date','')) != NULL ? date('m-d-Y h:i a', strtotime($is_death->get('death_date',''))) : $discharge_dt;
            } else {
                if ($counterFirst > 1) {
                    if ($phic) {
                        $admission_date = $start;
                        $discharge_date = $end;
                    } else {
                        $admission_date = $admission_dt;
                        $discharge_date = ($is_death->get('death_date','') != '0000-00-00 00:00:00' && $is_death->get('death_date','')) != NULL ? date('m-d-Y h:i a', strtotime($is_death->get('death_date',''))) : $discharge_dt;
                    }
                } elseif ($counterSecond > 1) {
                    if ($phic) {
                        $admission_date = $start;
                        $discharge_date = $end;
                    } else {
                        $admission_date = $admission_dt;
                        $discharge_date = ($is_death->get('death_date','') != '0000-00-00 00:00:00' && $is_death->get('death_date','')) != NULL ? date('m-d-Y h:i a', strtotime($is_death->get('death_date',''))) : $discharge_dt;
                    }
                } else {
                    $admission_date = $admission_dt;
                    $discharge_date = ($is_death->get('death_date','') != '0000-00-00 00:00:00' && $is_death->get('death_date','')) != NULL ? date('m-d-Y h:i a', strtotime($is_death->get('death_date',''))) : $discharge_dt;
                }
            }
        } else {
            $admission_date = $admission_dt;
            $discharge_date = ($is_death->get('death_date','') != '0000-00-00 00:00:00' && $is_death->get('death_date','')) != NULL ? date('m-d-Y h:i a', strtotime($is_death->get('death_date',''))) : $discharge_dt;
        }
        
        $params1 = [
            'hci_name' => 'Southern Philippines Medical Center',
            'building_name' => 'J.P Laurel, Bajada',
            'city' => 'Davao City',
            'province' => 'Davao del Norte',
            'zipcode' => '8000',
            'pan' => 'H11018319',
            'patient_name_last' => $person->name_last == NULL ? '' : strtoupper($person->name_last),
            'patient_name_first' => $person->name_first == NULL ? '' : strtoupper($person->name_first),
            'patient_name_suffix' => $person->suffix == NULL ? '' : strtoupper($person->suffix),
            'patient_name_middle' => $person->name_middle == NULL ? '' : strtoupper($person->name_middle),
            'pin' => $insurance_no[0]['insurance_nr'] ? $insurance_no[0]['insurance_nr'] : '',
            'age' => $person->getFullAge(),
            'sex' => $person->gender,
            'admitting_diagnosis' => $admitting_diagnosis ? $admitting_diagnosis : '',
            'admission_date' => $admission_date,
            'date_discharged' => $discharge_date,
            'present_illness' => $present_illness ? $present_illness->history : '',
            'logo_path' => getcwd() . $logo_path,
            'medical_history' => $medicalHistory ? ($medicalHistory->summary == null ? '' : $medicalHistory->summary) : '',
            'date_signed' => date('m-d-Y'),
            'first_case' => $f_code ? $f_code : '',
            'second_case' => $s_code ? $s_code : '',
            'vital_bp' => $bp != '/' ? $bp . ' mmHg' : '',
            'vital_hr' => $preassessment->get('pulse_rate') != null ? $preassessment->get('pulse_rate','') . ' /min.' : '',
            'vital_rr' => $preassessment->get('resp_rate') != null ? $preassessment->get('resp_rate','') . ' /min.' : '',
            'vital_temp' => $preassessment->get('temperature') != null ? $preassessment->get('temperature','') : '',
            'name_of_hci' => $hci_institution->get('name_of_hci','') ? $hci_institution->get('name_of_hci','') : '',
            'referral_reason' => $hci_institution->get('referral_reason','') ? $hci_institution->get('referral_reason','') : '',
            'is_hci' => $hci_institution->get('is_hci',''),
            'treatment' => $outcome_treatment->get('treatment',''),
            'reason' => $outcome_treatment->get('treatment','') == '5' ? $outcome_treatment->get('reason','') : '',
            'date_gravity' => $date_gravity,
            'date_parity' => $date_parity,
            'T' => $ob_gyn_preg->get('no_full_term_preg',''),
            'P' => $ob_gyn_preg->get('no_premature',''),
            'A' => $ob_gyn_preg->get('no_abortion',''),
            'L' => $ob_gyn_preg->get('no_living_children',''),
            'last_period_menstrual' => $last_period_menstrual,
            'chief_complaint' => $chief_complaint->get('chief_complaint') == null ? '' : $chief_complaint->get('chief_complaint'),
            'discharge_diagnosis' => $chief_complaint->get('discharge_diagnosis') == null ? '' : htmlspecialchars($chief_complaint->get('discharge_diagnosis')),
        ];

        $params = collect([])
        ->merge($params1)
        ->merge($sign_and_symptoms)
        ->merge([
            'medical_history' => $medicalHistory ?  ($medicalHistory->summary == null ? '' : $medicalHistory->summary) : '',
        ])
        ->merge($peDeatails)
        ->toArray();

        // $file_name = 'cf4_'.auth()->user()->personnel_id;
        $report_path = "cf4/cf4_print_page.jrxml";
        $jasper = new JasperReport();
        $jasper->showReport($report_path, $params, $data, 'PDF');
    }


    private function getArrayValue($array, $indexs = [], $default = '')
    {
        $val = $default;
        foreach($indexs as $key => $value){

            if(isset($array[$value])){
                if($key < count($indexs)-1){
                    unset($indexs[$key]);
                    $resp = $this->getArrayValue($array[$value], $indexs, 'NULL my Frient');
                    if($resp == 'NULL my Frient')
                        return $default;
                    else
                        $val = $array[$value];
                }
                else{
                    $val = $array[$value];
                }
            }
            else
                return $default;
        }

        return $val;

    }

    public function checkMandatoryFields(){
        $soap = $this->_soap();
        $vital = $this->_vitalSign();
        $illness = $this->_illness();
        $medical = $this->_medical();
        $pregnant = $this->_pregnant();
        $medicines = $this->_medicines();
        $course = $this->_courseward();
        $pertinent = $this->_pertinent();
        $examination = $this->_examination();
        $endCare = $this->_endCare();

        $msg = collect();

        if($soap) {
            $msg = $msg->merge($soap);
        }

        if($vital) {
            $msg = $msg->merge($vital);
        }

        if($illness) {
            $msg = $msg->merge($illness);
        }

        if($medical) {
            $msg = $msg->merge($medical);
        }

        if($pregnant) {
            $msg = $msg->merge($pregnant);
        }

        if($pertinent) {
            $msg = $msg->merge($pertinent);
        }

        if($examination) {
            $msg = $msg->merge($examination);
        }

        if($medicines) {
            $msg = $msg->merge($medicines);
        }

        if($course) {
            $msg = $msg->merge($course);
        }

        if($endCare) {
            $msg = $msg->merge($endCare);
        }
        
        return $msg->toArray();
    }

    public function _examination(){
        
        $examination = $this->getPhysicalExaminationDetails();
        $msg = [];
        if(empty($examination)){
            $msg[] = 'Physical Examination ';
        }
        
        return $msg;
    }

    public function _endCare(){
        $outcome_treatment = collect(EndCare::query()->where('encounter_no',$this->_encounter->encounter_no)->first());
        $msg=[];
        if($outcome_treatment->get('treatment') == null || trim($outcome_treatment->get('doc_advice','')) == '' || ($outcome_treatment->get('treatment') == 5 && trim($outcome_treatment->get('reason','')) == '')){
            $msg[] = 'End of Care > Outcome of Treatment, Doctor\'s Advice. ';
        }
        return $msg;
    }


    

    public function _pertinent(){
        $data = $this->getSignsAndSymptoms(); 
        $msg = [];
        if( count($data) == 2 && $data['opt_2_values'] == '' && $data['opt_3_values'] == '' ){
            $msg[] = "Pertinent Signs is required atleast one item, ";
        }
        
        return $msg;
    }

    public function _courseward(){
        $data = $this->courseWard($this->_encounter->encounter_no);
        $msg = [];
        if (empty($data)) {
            $msg[] = "Doctor's Order is required atleast 1 order, ";
        }
        return $msg;
    }

    public function _medicines(){
        
        $data = $this->getMedicine();
        $msg = [];

        $msg_ = '';
        
        if($data) {
            foreach ($data as $key => $value) {
                if(empty($msg_)){
                    if($value['frequency'] == 'NONE') {
                        $msg_  .= 'Frequency, ';
                    }
        
                    if($value['route'] == 'NONE') {
                        $msg_  .= 'Route';
                    }
                }
            }
            
            if(!empty($msg_)){
                $msg_ = rtrim($msg_, ", ");
                $msg_ .= ".";

                $msg[] = "Drug's & Medicines > {$msg_}";
            }
            
        }
        return $msg;
    }

    public function _pregnant(){
        $pregnant = PregnantHistory::query()
            ->where('encounter_no', $this->_encounter->encounter_no)
            ->first();
        $yes = 'Y';
        $msg = [];
        // dd($pregnant['is_applicable_pregnant']);
        if ($pregnant['is_applicable_pregnant'] == $yes) {
            if (!is_numeric($pregnant['date_gravidity']) || !is_numeric($pregnant['date_parity'])) {
                $msg[] = "Medical History > Pregnant History: ";
            }

            if (!is_numeric($pregnant['date_gravidity'])) {
                $msg[0] .= 'Number of Pregnancy to Date – Gravidity, ';
            }

            if (!is_numeric($pregnant['date_parity'])) {
                $msg[0] .= 'Number of Delivery to Date – Parity, ';
            }
        }
        return $msg;

    }

    public function _medical(){
        $pastMed = PastMedicalHistory::query()
            ->where('encounter_no', $this->_encounter->encounter_no)
            ->first();
        $msg = [];
        if (empty($pastMed)) {
            $msg[] = 'Medical History > Past Medical History ';
        }
        return $msg;

    }

    public function _illness(){
        $present_illness = PresentIllness::query()
            ->where('encounter_no', $this->_encounter->encounter_no)
            ->first();
        $msg = [];
        if (empty($present_illness)) {
            $msg[] = 'Medical History > History of Present Illness ';
        }
        return $msg;
    }

    public function _vitalSign(){
        $data = $this->getVitalSigns();
        $msg1 = [];
        $msg = "";
        if(empty($data)) {
            $msg1[] = 
            'Vital Signs is Required ';
        } else {
            if(!is_numeric($data['systole']) || !is_numeric($data['diastole']) || !is_numeric($data['pulse_rate']) || !is_numeric($data['resp_rate']) || !is_numeric($data['temperature'])) {
                $msg = 'Vital Signs: ';
            }

            if (!is_numeric($data['systole'])) {
                $msg .= 'Systole, ';
            }

            if (!is_numeric($data['diastole'])) {
                $msg .= 'Diastole, ';
            }

            if (!is_numeric($data['pulse_rate'])) {
                $msg .= 'Pulse Rate, ';
            }

            if (!is_numeric($data['resp_rate'])) {
                $msg .= 'Resp Rate, ';
            }

            if (!is_numeric($data['systole']) && !is_numeric($data['diastole']) && !is_numeric($data['pulse_rate']) && !is_numeric($data['resp_rate']) && !is_numeric($data['temperature'])) {
                $msg .= "\n".'Temperature.';
            } else {
                if (!is_numeric($data['temperature'])) {
                    $msg .= 'Temperature.';
                }
            }

            if(!empty($msg))
                $msg1[] = $msg;
        }
        return $msg1;
        
    }

    public function _soap(){
        $soap = $this->getChiefComplaint(true);
        $msg = [];
        $chief_com = $soap['subjective']['names'];
        $free_text = $soap['subjective']['others']['value'];
        $final_diag = $soap['final_diag']['value'];

        if ($chief_com == '' && $free_text == null) {
            $msg[] = "SOAP > Subjective: Chief Complaint / Other Complaint ";
        }
        if($final_diag == null){
            $msg[] = "SOAP > Assessment: Final Diagnosis ";
        }
        return $msg;

    }

    public function getChiefComplaint($validate=false)
    {
        $soap_service = new SoapService($this->_encounter);
        $data = $soap_service->getSoap();

        if($validate){
            return $data;
        }

        $chief_com = $data['subjective']['names'];
        $free_text = $data['subjective']['others']['value'];
        $final_diag = $data['final_diag']['value'];
        $admitting_diag = $data['clinical_imp']['admitting_diag'];

        $combine['chief_complaint'] = $chief_com . ' - ' . $free_text;
        $combine['discharge_diagnosis'] = $final_diag;
        $combine['admitting_diagnosis'] = $admitting_diag;

        return $combine;
    }

    public function getPhysicalExaminationDetails()
    {
        $service = ExaminationService::init($this->_encounter->encounter_no);
        $labels = $service->getPhysicalExamCategoriesArray();

        $details = [];
        $id = [];
        $check = 0;
        foreach ($labels as $key => $label) {
            $label = $labels[$key]['label'];
            $details = $service->getEncounterFindingByCategory($label);
            foreach ($details['myfindings'] as $key_id => $value) {
                $label = SUBSTR($label, 0, 2);
                if ($key_id == 'others') {
                    $id['finding_' . $key_id . '_' . $label] = 1;
                    $id['value_' . $key_id . '_' . $label] = $value;
                } else {
                    $id['finding_' . $key_id] = 1;
                    if ($key_id == '2') {
                        $id['value_' . $key_id . '_' . $label] = $value;
                    }
                }
            }
        }
        return $id;
    }

    public function getVitalSigns()
    {

        $service = new PreAssessmentService($this->_encounter);
        $vitalsigns = $service->getVitalSigns();
        $dates = [];
        foreach ($vitalsigns as $key => $vitalsign) {
            $dates [] = $vitalsign['date'];
         }

         if(count($dates) <= 0)
            return [];
 
         $min_date = date("Y-m-d h:i",min(array_map('strtotime',$dates)));
 
         foreach ($vitalsigns as $key => $vitalsign_data) {
             $ref_date = date("Y-m-d h:i", strtotime($min_date));
             $base_date = date("Y-m-d h:i", strtotime($vitalsign_data['date']));
 
             if ( $min_date == $base_date ){
                 $data = $vitalsign_data;
             }
         }
        return count($vitalsigns) > 0 ? $vitalsigns[count($vitalsigns)-1] : [];
    }



    public function getSignsAndSymptoms()
    {
        $sign_and_symptoms = new PertinentSignAndSymptomsService($this->_encounter);
        $data = $sign_and_symptoms->getSelectedData();
        $pertinent = [];
        $for_opt2 = [];
        $for_opt3 = [];
        $for_opt2['opt_2_values'] = '';
        $for_opt3['opt_3_values'] = '';
        for ($i = 0; $i < count($data['data']); $i++) {
            $data_name = SUBSTR($data['data_name'][$i], 0, 3);
            $data_id = $data['data'][$i];

            if (!empty($data['opt_2'][$i])) {
                $pertinent['opt_2'] = 1;
            }
            if (!empty($data['opt_3'][$i])) {
                $pertinent['opt_3'] = 1;
            }
            $pertinent['sign_and_symp_' . $data_id] = 1;
        }
        for ($i = 0; $i < count($data['opt_2']); $i++) {
                
            $for_opt2['opt_2_values'] .=   $data['opt_2'][$i] . ' ,';
        }
        for ($i = 0; $i < count($data['opt_3']); $i++) {
            $for_opt3['opt_3_values'] .=  $data['opt_3'][$i] . ' ,';
        }
        $for_opt2['opt_2_values'] = rtrim($for_opt2['opt_2_values'], ' ,');
        $for_opt3['opt_3_values'] = rtrim($for_opt3['opt_3_values'], ' ,');

        $data = collect($for_opt2)->merge($for_opt3);
        $params = $data->merge($pertinent);

        return $params->toArray();
    }

    
    public function getMedicine()
    {
        $data = collect();

        $medicines_services = new MedicinesServices();

        $meds_outside = collect($medicines_services->outsideMedication($this->_encounter->encounter_no));
        $parent_encounter = $this->_encounter->hisEncounter->parent_encounter_nr;
        $final_bill = $this->_encounter->hisEncounter->billing ? $this->_encounter->hisEncounter->billing->is_final : false;

        if ($parent_encounter && !($final_bill)) {
            $medicines_nr = collect($medicines_services->medication($this->_encounter->encounter_no));
            $medicines_parent = $medicines_services->medication($parent_encounter);
            $meds_inside = $medicines_nr->merge($medicines_parent)->toArray();
        } else {
            $meds_inside = $medicines_services->medication($this->_encounter->encounter_no);
        }

        
        $meds = $meds_outside->merge($meds_inside)->toArray();
        $medicine = [];
        $route = $frequency = '';
        $form_desc = [
            'form_desc' => '',
            'form_code' => ''
        ];
        $dosage = "";
        for ($i = 0; $i < count($meds); $i++) {
            switch ($meds[$i]['meds']) {
                case "OUTSIDE":
                    $drugCode = $meds[$i]['drug_code'];
                    $gen_name = $meds[$i]['generic_name'];
                    $brand_name = $meds[$i]['brand_name'];
                    $gen_code = $meds[$i]['gen_code'];
                    // dd($meds[$i]);
                    $insMeds = $medicines_services->getInsideMedicines($gen_code);
                    $his_gen = $insMeds ? $insMeds->generic : null;

                    if ($drugCode) {
                        if ($gen_name) {
                            $generic_name = $gen_name;
                        } elseif ($brand_name) {
                            $generic_name = $brand_name;
                        } else {
                            $generic_name = $his_gen;
                        }
                    } elseif ($gen_name) {
                        if ($gen_name) {
                            $generic_name = $gen_name;
                        } elseif ($brand_name) {
                            $generic_name = $brand_name;
                        } else {
                            $generic_name = $his_gen;
                        }
                    } elseif ($brand_name) {
                        if ($brand_name) {
                            $generic_name = $brand_name;
                        } else {
                            $generic_name = $his_gen;
                        }
                    } else {
                        $generic_name = $his_gen;
                    }
                    $form_desc['form_desc'] = $meds[$i]['form_desc'];
                    $route = $meds[$i]['route'];
                    $frequency = $meds[$i]['frequency'];
                    $dosage = isset($meds[$i]['dosage']) ? $meds[$i]['dosage'] : null;


                    break;
                default:
                    $drugCode = $meds[$i]['drug_code'];
                    $gen_name = $meds[$i]['generic_name'];
                    $bestellnum = $meds[$i]['item_id'];
                    $refno = $meds[$i]['refno'];
                    $form_code =$meds[$i]['form_code'];

                    if ($drugCode) {
                        $generic_name = $gen_name;
                    } else if ($gen_name) {
                        $generic_name = $gen_name;
                    } else {
                        $generic_name = $gen_name;
                    }

                    $form_desc = PhilMedicineForm::query()->find($form_code);
                    $getPharmaItemsCF4 = $medicines_services->medicine_details($refno, $bestellnum);
                    if($getPharmaItemsCF4){
                        $dosage = $getPharmaItemsCF4->dosage;
                        $route = $getPharmaItemsCF4->route;
                        $frequency = $getPharmaItemsCF4->frequency;

                    }

                    break;
            }

            $strength_code = PhilMedicineStrength::query()->where(
                    'strength_code',$meds[$i]['strength_code']
                )->first();
            
            $medicine[$i] = [
                    'generic_name'  => $generic_name,
                    'strength_desc' => $strength_code != null ? $strength_code->strength_disc : 'NONE',
                    'form_desc'     => $form_desc['form_desc'] != null ? $form_desc['form_desc'] : 'NONE',
                    'price'         => 0,// $meds[$i]['price'] // DB does not have price column
                    'quantity'      => $meds[$i]['quantity'],
                    'dosage'        => $dosage == null ? "NONE" : $dosage,
                    'frequency'     => $frequency == null ? "NONE" : $dosage.' '.$frequency,
                    'route'         => $route == null ? "NONE" : $route,
                    'total_cost'    => number_format($meds[$i]['total_cost'], 2),
            ];
        }

        $params = $data->merge($medicine);

        return $params->toArray();
    }



    private function courseWard($encounter)
    {
        $model = EncounterCourseward::query()
        ->where('encounter_no', $encounter)
        ->where('is_deleted', 0)
        ->get();
        
        $courseward=[];
        for ($x = 0; $x < count($model); $x++) {
            // if($model->is_deleted == '0'){
            $order_date = $model[$x]->order_date;
            $courseward[] = [
                'remarks' => $model[$x]->action,
                'requestDate' => date("Y-m-d H:i:s a", strtotime($order_date))
            ];
            // }

        }
        $keys = array_column($courseward, 'requestDate');
        array_multisort($keys, SORT_ASC, $courseward);
        return $courseward;
    }

    public function getCourseWard()
    {
       return $this->courseWard($this->_encounter->encounter_no);
    }

    public function getCourseWardParentEnc()
    {
       return $this->courseWard($this->_encounter->parent_encounter_nr);
    }

    public function getInsuranceNo()
    {
        $his_services = new HisServices($this->_encounter, strftime('%Y-%m-%d %H:%M:%S'));
        $insurance_no = $his_services->getInsuranceNo();
        return $insurance_no ? $insurance_no : null;
    }

    public function getCaseRateCode()
    {
        $his_services = new HisServices($this->_encounter, strftime('%Y-%m-%d %H:%M:%S'));
        $codes = $his_services->getCaseRateCode();

        return $codes ? $codes : [];
    }


}