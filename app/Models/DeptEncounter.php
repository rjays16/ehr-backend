<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property String $deptenc_no
 * @property String $encounter_no
 * @property String $deptenc_code
 * @property String $deptenc_date
 * @property String $er_areaid
 * @property String $admit_diagnosis
 * @property String $admit_id
 * @property String $admit_dt
 * @property String $is_deleted
 * @property String $modify_id
 * @property String $modify_dt
 * @property String $create_id
 * @property String $create_dt
 */
class DeptEncounter extends Model
{
    protected $table = 'smed_dept_encounter';

    protected $primaryKey = 'deptenc_no';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    const TYPE_PHS = 'phs';
    const TYPE_OPE = 'ope';
    const TYPE_IPE = 'ipe';
    const TYPE_ERE = 'ere';

    const TYPE_OPD = 'OPD';
    const TYPE_IPD = 'IPD';
    const TYPE_ER = 'ER';

    protected $fillable = [
        'deptenc_no',
        'encounter_no',
        'deptenc_code',
        'deptenc_date',
        'er_areaid',
        'admit_diagnosis',
        'admit_id',
        'admit_dt',
        'is_deleted',
        'modify_id',
        'modify_dt',
        'create_id',
        'create_dt',
    ];

    protected $hidden = [
        "op_areaid",
        "room_no",
        "is_transferred",
        "specialty_code",
        "is_medicolegal",
        "is_DOA",
        "ac_id",
        "jc_id",
        "weeks",
        "impression",
        "admit_id",
        "is_deleted",
        "modify_id",
        "create_id",
        "cancel_id",
        "cancel_dt",
        "reasonfor_transfer",
        "transfer_dt",
        "condition",
        "refferred_diagnosis",
        "is_confidential",
        "attending_dr",
        "house_doctor",
    ];


    public function encounter(){
        return $this->belongsTo(Encounter::class, 'encounter_no');
    }

    public function encounterNoDischarged(){
        return $this->belongsTo(Encounter::class, 'encounter_no');
    }

    public function area(){
        return $this->belongsTo(AreaCatalog::class, 'er_areaid', 'area_id');
    }

    public function opArea(){
        return $this->belongsTo(AreaCatalog::class, 'op_areaid', 'area_id');
    }

    public function nurseWard(){
        return $this->hasOne(NurseWardCatalog::class, 'dept_id', 'er_areaid');
    }

    public function getEncounterTypeHisEquivalent(){
        switch (strtoupper($this->deptenc_code)){
            case 'OPE':
                return 'OPD';
            case 'ERE':
                return 'ER';
            case 'IPE':
                return 'IPD';
            case 'PHS':
                return 'PHS-OUTPATIENT';
            default:
                return strtoupper($this->deptenc_code);
        }
    }
}
