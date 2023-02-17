<?php
/**
 * Created by PhpStorm.
 * User: Deboner Dulos
 * Date: 8/24/2019
 * Time: 6:41 PM
 */
namespace App\Models;

use App\Models\HIS\HisEncounter;
use App\Models\HIS\HisPerson;
use App\Models\Mongo\PatientPreAssessment;
use App\Services\Doctor\VitalSign\PreAssessmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property String $encounter_no
 * @property String $encounter_date
 * @property String $spin
 * @property String $discharge_dt
 * @property String $is_discharged
 * @property String $modify_id
 * @property String $modify_dt
 * @property String $create_id
 * @property String $is_cancel
 * @property String $cancel_dt
 * @property String $cancel_id
 * @property String $admit_diagnosis2
 * @property String $parent_encounter_nr
 */
class Encounter extends Model
{
    protected $table = 'smed_encounter';

    protected $primaryKey = 'encounter_no';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'encounter_no',
        'encounter_date',
        'spin',
        'discharge_dt',
        'is_discharged',
        'modify_id',
        'modify_dt',
        'create_id',
        'create_dt',
        'is_cancel',
        'cancel_dt',
        'cancel_id',
        'admit_diagnosis2',
        'parent_encounter_nr',
    ];

    protected $hidden = [
        "mgh_dt",
        "is_mgh",
        "death_dt",
        'is_infectious',
        "history",
        "disposition_id",
        "is_pregnant",
        "hci_code",
        "refer_todoctor",
        "mgh_id",
        "discharge_id",
        "is_package",
    ];

    public function spin0(){
        return $this->belongsTo(PatientCatalog::class, 'spin');
    }


    public function hisspin0(){
        return $this->belongsTo(HisPerson::class, 'spin','pid');
    }

    

    public function endcare(){
        return $this->belongsTo(EndCare::class, 'encounter_no', 'encounter_no');
    }

    public function assessments(){
        return $this->hasMany(EncounterAssessment::class, 'encounter_no', 'encounter_no');
    }

    public function medicationSummary(){
        return $this->belongsTo(MedicalHistorySummary::class, 'encounter_no', 'encounter_no');
    }

    public function favorite(){
        return $this->belongsTo(FavoritePatient::class, 'encounter_no', 'encounter_no');
    }

    public function medicalHistorySummary(){
        return $this->belongsTo(MedicalHistorySummary::class, 'encounter_no', 'encounter_no');
    }

    public function presentIllnes(){
        return $this->belongsTo(PresentIllness::class, 'encounter_no', 'encounter_no')
                ->with("personnel");
    }


    public function socialHistory(){
        return $this->belongsTo(SocialHistory::class, 'encounter_no', 'encounter_no')
                ->where("is_deleted", SocialHistory::IS_NOT_DELETED)
                ->with("personnel");
    }

    public function menstrualHistory(){
        return $this->belongsTo(MenstrualHistory::class, 'encounter_no', 'encounter_no')
                ->with("personnel")
                ->where("is_deleted", MenstrualHistory::IS_NOT_DELETED);
    }

    public function pastMedicalHistory(){
        return $this->hasMany(PastMedicalHistory::class, 'encounter_no','encounter_no')
                ->with("philDisease", "personnel");
    }

    public function surgicalHistory(){
        return $this->hasMany(SurgicalHistory::class, 'encounter_no','encounter_no')
                ->with("personnel");
    }

    public function familyHistory(){
        return $this->hasMany(FamilyHistory::class, 'encounter_no','encounter_no')
            ->with("philDisease", "personnel");
    }

    public function immunizationRecord(){
        return $this->belongsTo(ImmunizationRecord::class, 'encounter_no','encounter_no')
                ->with(["childImmu", "youngImmu", "pregImmu", "elderlyImmu","personnel"])
                ->where("is_deleted", ImmunizationRecord::IS_NOT_DELETED);
    }

    public function pregnantHistory(){
        return $this->belongsTo(PregnantHistory::class, 'encounter_no','encounter_no')
                ->with("personnel")
                ->where("is_deleted", PregnantHistory::IS_NOT_DELETED);
    }

    public function thisDoctorFavorite($personnel_id){
        return $this->belongsTo(FavoritePatient::class, 'encounter_no', 'encounter_no')
                    ->where('doctor_id', $personnel_id);
    }

    public function unfinalizedBatchOrderNote(){
        
        return $this->belongsTo(BatchOrderNote::class, 'encounter_no', 'encounter_no')
                ->where(
                    function ($e) {
                        $e->where('is_finalized', 0)
                                ->where('doctor_id', [auth()->user()->personnel_id]);
                    }
                );
    }


    public function batchOrderNotes(){
        
        return $this->hasMany(BatchOrderNote::class, 'encounter_no', 'encounter_no')
                ->where(
                    function ($e) {
                        $e->where('is_finalized', 1)
                                ->orWhere('doctor_id', [auth()->user()->personnel_id]);
                    }
                )
                ->orderByDesc('create_dt');
    }

    public function referralorders(){
        
        return $this->hasMany(BatchOrderNote::class, 'encounter_no', 'encounter_no');
    }

    public function referralFinalizedorders(){
        
        return $this->hasMany(BatchOrderNote::class, 'encounter_no', 'encounter_no')
                    ->where('is_finalized',1);
    }

    public function triageAssessments(){
        return $this->hasMany(EncounterAssessment::class, 'encounter_no', 'encounter_no')
                    ->where('form_code', PreAssessmentService::PREASSESSMENT)
                    ->where('is_deleted',0);
    }


    public function hisEncounter(){
        return $this->belongsTo(HisEncounter::class, 'encounter_no', 'encounter_nr');
    }

    /**
     * Get all doctors to this encounter
     * @return array
     */
    public function getPhysicians():array
    {
        // fetch doctors
        
        $hisEnc = $this->hisEncounter;
        if(is_null($hisEnc))
            throw new EhrException('Encounter does not exist on HIS', 404);
            
        $physicians = [];
        if($hisEnc->attendingPhysician)
            $physicians[] = [
                'name' => $hisEnc->attendingPhysician->p->drFullname(),
                'department' => $hisEnc->attendingPhysicianDept->name_formal,
                'type' => 'Attending Physician'
            ];
        
        if($hisEnc->attendingPhysician)
            $physicians[] = [
                'name' => $hisEnc->admittingPhysician->p->drFullname(),
                'department' => $hisEnc->admittingPhysicianDept->name_formal,
                'type' => 'Admitting Physician'
            ];

        return $physicians;
    }

    

    public function latestEncounterAssessment(){
        return $this->hasOne(EncounterAssessment::class, 'encounter_no', 'encounter_no')
            ->where('assess_type','PA')
            ->orderByDesc('assessment_date');
    }


    public function currentDeptEncounter(){
        return $this->hasOne(DeptEncounter::class, 'encounter_no', 'encounter_no')
            ->orderByDesc('deptenc_date');
    }


    public function deptEncounters(){
        return $this->hasMany(DeptEncounter::class, 'encounter_no');
    }

    public function deptEncounter(){
        return $this->hasOne(DeptEncounter::class, 'encounter_no');
    }


    public function encounterRefHCI(){
        return $this->hasOne(ReferralInstitution::class, 'encounter_no');
    }

    public function encounterDeptEncounter(){
        return $this->hasOne(DeptEncounter::class, 'encounter_no')
            ->where(function (Builder $query){
                return $query->join("smed_encounter as se", "se.encounter_no", "=", "smed_dept_encounter.encounter_no");
            });
    }

    public function encounterDxpr(){
        return $this->hasOne(EncounterDxpr::class, 'encounter_no')->orderByDesc('deptenc_date');
    }

    public function encounterEndCare(){
        return $this->hasOne(EndCare::class, 'encounter_no')->orderByDesc('deptenc_date');
    }

    public function assessmentsData($encounter){
        return self::query()->where("encounter_no", $encounter)
            ->with("assessments")
            ->get();
    }

    public function getPersonEncounters($pid){
        return DB::table("smed_encounter as se")
            ->select(DB::raw('se.encounter_no, se.encounter_date, se.discharge_dt,sde.deptenc_code, sac.area_desc'))
            ->where("se.spin", $pid)
            ->join('smed_dept_encounter AS sde', 'sde.encounter_no', '=', 'se.encounter_no')
            ->join("smed_area_catalog as sac", "sac.area_id", "=", "sde.er_areaid")
            ->orderByDesc('sde.deptenc_date')
            ->get();
    }



}
