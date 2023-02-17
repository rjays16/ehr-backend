<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property String $id
 * @property String $encounter_no
 * @property String $icd_code
 * @property String $alt_diagnosis
 * @property String $create_id
 * @property String $create_dt
 * @property String $modify_id
 * @property String $modify_dt
 * @property String $is_deleted
 */
class EncounterDxpr extends Model
{
    protected $table = 'smed_encounter_dxpr';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'encounter_no',
        'icd_code',
        'alt_diagnosis',
        'create_id',
        'create_dt',
        'modify_id',
        'modify_dt',
        'is_deleted',
    ];

    protected $hidden = [
        "service_id",
        "doctor_id",
        'laterality',
        'is_final',
        'related_procedure'
    ];


    public function encounter(){
        return $this->belongsTo(Encounter::class, 'encounter_no');
    }

    public function icdCode(){
        return $this->belongsTo(Icd10Code::class, 'icd_code');
    }


    public function checkExisting($data)
    {
        return !self::query()
                ->where('encounter_no', $data['id'])
                ->where('icd_code', $data['icd_code'])
                ->where('is_deleted', 0)
                ->first() ? false : true;
    }
}
