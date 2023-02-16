<?php

namespace App\Models;

use App\Models\Mongo\PatientPreAssessment;
use App\Models\Mongo\Tracer\TracerPatientPreassessmentEncounter;
use App\Models\Mongo\Tracer\TracerTriagePatientPreassessmentEncounter;
use Illuminate\Database\Eloquent\Model;

/**
 * @property String $id
 * @property String $encounter_no
 * @property String $deptenc_no
 * @property String $assessment_date
 * @property String $model_id
 * @property String $document_id
 * @property String $form_code
 * @property String $assess_type
 * @property String $create_id
 * @property String $create_dt
 * @property String $modify_dt
 * @property String $is_deleted
 * @property String $modify_id
 */
class EncounterAssessment extends Model
{
    protected $table = 'smed_encounter_assessment';

    protected $form_code = 'preassessment';
    protected $asses_type = 'vs';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'encounter_no',
        'deptenc_no',
        'assessment_date',
        'model_id',
        'document_id',
        'form_code',
        'assess_type',
        'create_id',
        'create_dt',
        'modify_dt',
        'is_deleted',
        'modify_id',
    ];

    public function encounter(){
        return $this->belongsTo(Encounter::class, 'encounter_no');
    }

    /**
     * @return TracerPatientPreassessmentEncounter
     */
    public function soapAssessment()
    {   
        return  new TracerPatientPreassessmentEncounter($this);
    }

    /**
     * @return TracerTriagePatientPreassessmentEncounter
     */
    public function triageAssessment()
    {   
        return  new TracerTriagePatientPreassessmentEncounter($this);
    }

    /**
     * @return PersonnelCatalog
     */
    public function modifiedPersonnel()
    {   
        return $this->belongsTo(PersonnelCatalog::class, 'modify_id', 'personnel_id');
    }

    public function getEncounterAssessments($encounter){
        return self::query()
            ->where("encounter_no", $encounter)
            ->get();
    }

    public function getAssessmentsData($encounter){
        return self::query()
            ->where("encounter_no", $encounter)
            ->with("encounter.spin0.person")
            ->get();
    }




    
}
