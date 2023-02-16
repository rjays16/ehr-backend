<?php


namespace App\Services\Doctor\PMH;

use App\Exceptions\EhrException\EhrException;
use App\Models\Encounter;
use App\Models\FamilyHistory;
use App\Models\ImmunizationRecord;
use App\Models\MenstrualHistory;
use App\Models\PastMedicalHistory;
use App\Models\PregnantHistory;
use App\Models\SocialHistory;
use App\Models\SurgicalHistory;
use App\Models\MedicalHistorySummary;
use App\Services\FormActionHelper;
use Illuminate\Support\Facades\Auth;
class ParagraphFormService
{

    /**
     * @var Encounter $encounter
     */
    private $encounter;
    private $encounter_no;
    private $spin;

    function __construct(Encounter $encounter=null)
    {
        $this->encounter = $encounter;
        $this->encounter_no = $encounter->encounter_no;
        $this->spin = $encounter->spin;
    }

    public static function init($encounter){
        $encounter = Encounter::query()->find($encounter);
        if (is_null($encounter))
            throw new EhrException('Encounter was not found. ', 404);
        /**
         * @var Encounter $encounter
         */
        return new ParagraphFormService($encounter);
    }


    public function paragraphForm()
    {
        $pastmedical = $this->paragraphForPastMed();
        $surgHis = $this->paragraphForSurgicalHis();
        $famHis = $this->paragraphForFamHis();
        $immunizaion = $this->paragraphFormImmunization();
        $socialHis = $this->paragraphForSocialHis();

        $model = (new MedicalHistorySummary())->medicalHistorySummary($this->encounter_no);
        $summary = $pastmedical . "" . $surgHis . "" . $famHis . "" . $socialHis . "" . $immunizaion;

        $modifier = FormActionHelper::getFormTimeStamp();
        if(empty($model)){
           $model = new MedicalHistorySummary();
           $model->encounter_no = $this->encounter_no;
           $model->spin = $this->spin;
           $model->created_at = $modifier['modified_dt'];
           $model->created_by = $modifier['modified_by'];
        }
        $model->summary = $summary;
        $model->updated_at = $modifier['modified_dt'];
        $model->updated_by = $modifier['modified_by'];

        if(!$model->save())
            throw new EhrException('Unable to save Medical History ', 404);

    }

    public function paragraphForPastMed(){
        $data = "";
        $model = (new PastMedicalHistory())->pastMedicalHistory($this->encounter_no);

        if(count($model) > 0) {
            $data .= "Past Medical History: ";
            foreach ($model as $pastMed) {
                if ($pastMed->specific_disease_description != "" && $pastMed->specific_disease_description != "None" && $pastMed->philDisease->mdisease_code != 998) {
                    $data = $data . " " . $pastMed->philDisease->mdisease_description . "-" . $pastMed->specific_disease_description . ". ";
                } else if ($pastMed->specific_disease_description != "" && $pastMed->specific_disease_description != "None") {
                    $data = $data . " " . $pastMed->specific_disease_description . ". ";
                } else {
                    $data = $data . " None.";
                }
            }
            $data .= "\n";
        }
        return $data;
    }

    public function paragraphForSurgicalHis()
    {
        $data = "";
        $model = (new SurgicalHistory())->surgicalHistory($this->encounter_no);
        if(count($model) > 0) {
            $data .= "Surgery: ";
            foreach($model as $surgicalHis) {
                if ($surgicalHis->description != "") {
                    $data .= $surgicalHis->description . " (" . $surgicalHis->date_of_operation . "), ";
                }
            }
            $data = rtrim($data, ", ");
            $data .= ".\n";
        }
        return $data;
    }

    public function paragraphForFamHis()
    {
        $data = "";
        $model = (new FamilyHistory())->familyHistory($this->encounter_no);
        if(count($model) > 0) {
            $data .= "Family History: ";
            foreach ($model as $famMed) {
                if ($famMed->philDisease->mdisease_code != 999 and $famMed->philDisease->mdisease_code != 998) {
                    $data = $data . " " . $famMed->philDisease->mdisease_description . "-" . $famMed->specific_disease_description . ". ";
                } else {
                    $data = $data . $famMed->specific_disease_description . ". ";
                }
                $data .= "\n";
            }
        }
        return $data;
    }

    public function paragraphForSocialHis()
    {
        $data = "";
        $model = (new SocialHistory())->socialHistory($this->encounter_no);
//        dd(($model));
        if (!empty($model)) {
            $data .= "Social History: ";
            if ($model->is_smoke == 'Y') {
                $data .= "Years of smoking, " . $model->years_smoking . ". ";
                $data .= "Average Stick per day, " . $model->stick_per_day . ". ";
                $data .= "Average Stick per year, " . $model->stick_per_year . ". ";
            }else{
                $data .= 'Non-smoker. ';
            }
            if ($model->is_alcohol == 'Y' or $model->is_alcohol == 'X') {
                $model->is_alcohol == 'Y' ? $data .= " using alcohol and consuming " .
                    $model->no_bottles . " bottles per day. " : $data .= "Quit using alcohol but consuming " .
                    $model->no_bottles . " bottles per day. ";
            }else{
                $data .= 'Non-alcoholic drinker. ';
            }
            if ($model->is_drug == 'Y') {
                $data .= "A drug user. ";
            }else{
                $data .= 'No history of drug use';
            }
            $data = rtrim($data, ", ");
            $data .= "\n";
        }

        return $data;
    }

    public function paragraphForMenstrualHis()
    {
        $data = "";
        $model = (new MenstrualHistory())->menstrualHistory($this->encounter_no);
        if(!empty($model)){
            if ($model->age_first_menstrual != "") {
                $data .= $model->age_first_menstrual . " years old of first menstrual, ";
            }
            if ($model->last_period_menstrual != "") {
                $data .= $model->last_period_menstrual . " date of last menstrual period, ";
            }

            if ($model->no_days_menstrual_period != "") {
                $data .= $model->no_days_menstrual_period . "days of menstrual period, ";
            }
            if ($model->interval_menstrual_period != "") {
                $data .= $model->interval_menstrual_period . " no of days of interval/cycle of menstruation, ";
            }

            if ($model->no_pads != "") {
                $data .= $model->interval_menstrual_period . " no of pads used per day, ";
            }

            if ($model->age_sex_intercourse != "") {
                $data .= $model->age_sex_intercourse . " years old of first sexual intercourse, ";
            }

            if ($model->birth_control_used != "") {
                $data .= "Using " . $model->age_sex_intercourse . " birth control method, ";
            }

            if ($model->is_menopause == "Y") {
                $data .= "Already in menopause stage ";
                if ($model->age_menopause != "") {
                    $data .= " and start at the age of " . $model->age_menopause . "years old, ";
                }
            }
            if ($model->remarks != "") {
                $data .= "having a remarks of " . $model->remarks . ", ";
            }

            $data = rtrim($data, ", ");
            $data .= ".";
        }
        return $data;
    }


    public function paragraphForPpregnantHis()
    {
        $data = "";
        $model = (new PregnantHistory())->pregnantHistory($this->encounter_no);
        if(!empty($model)){
            $data .= "For Pregnant History, ";
            if ($model->date_gravidity != "") {
                $data .= $model->date_gravidity . " as number of pregnancy to date – gravidity, ";
            }

            if ($model->date_parity != "") {
                $data .= $model->date_parity . " as number of pregnancy to date – parity, ";
            }

            if ($model->type_delivery != "") {
                $data .= "Using " . $model->type_delivery . " as type of delivery, ";
            }

            if ($model->no_full_term_preg != "") {
                $data .= $model->no_full_term_preg . " as number of full term pregnancy, ";
            }

            if ($model->no_premature != "") {
                $data .= $model->no_premature . " as number of premature pregnancy, ";
            }

            if ($model->no_abortion != "") {
                $data .= $model->no_abortion . " as number of abortion, ";
            }

            if ($model->no_living_children != "") {
                $data .= $model->no_living_children . " as number of living children, ";
            }

            if ($model->induced_hyper != "") {
                $data .= $model->induced_hyper . " – induced hypertension (pre – eclampsia), ";
            }

            if ($model->family_planning != "") {
                $data .= $model->family_planning . " for family planning counselling, ";
            }

            if ($model->remarks != "") {
                $data .= " having a remarks of" . $model->remarks . " for pregnant history, ";
            }

            $data = rtrim($data, ", ");
            $data .= ".";
        }

        return $data;
    }

    public function paragraphFormImmunization()
    {
        $data = "";
        $model = (new ImmunizationRecord())->immunizationRecord($this->encounter_no);

        if(!empty($model)){
            $data = "Immunization: ";
            if ($model->child_id != "") {
                $data .= "Child Immunization, " . $model->childImmu->imm_description . ". ";
            }

            if ($model->young_id != "") {
                $data .= "Adult Immunization, " . $model->youngImmu->imm_description . ". ";
            }

            if ($model->preg_id != "") {
                $data .= "Pregnant Immunization, " . $model->pregImmu->imm_description . ". ";
            }

            if ($model->elder != "") {
                $data .= "Elderly Immunization, " . $model->elderlyImmu->imm_description . ". ";
            }

            $data .= "\n";
        }
        return $data;
    }


}