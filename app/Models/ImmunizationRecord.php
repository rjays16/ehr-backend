<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $spin
 * @property $encounter_no
 * @property $child_id
 * @property $young_id
 * @property $preg_id
 * @property $elder_id
 * @property $other_code
 * @property $remarks
 * @property $is_deleted
 * @property $created_at
 * @property $updated_at
 * @property $created_by
 * @property $updated_by
 */

class ImmunizationRecord extends Model
{
    public $table = 'smed_immunization_record';
    const IS_DELETED = 0;
    const IS_NOT_DELETED = 0;
    public $fillable = [
        'id',
        'spin',
        'encounter_no',
        'child_id',
        'young_id',
        'preg_id',
        'elder_id',
        'other_code',
        'remarks',
        'is_deleted',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by'
    ];

    public function immunizationRecord($encounter){
        return self::query()
            ->where("encounter_no", $encounter)
            ->where("is_deleted", self::IS_NOT_DELETED)
            ->with(["childImmu", "youngImmu", "pregImmu", "elderlyImmu", "personnel.p"])
            ->first();
    }

    public function childImmu(){
        return $this->belongsTo(PhilImmchild::class, "child_id", "id");
    }

    public function youngImmu(){
        return $this->belongsTo(PhilImmyoungw::class, "young_id", "id");
    }

    public function pregImmu(){
        return $this->belongsTo(PhilImmpregw::class, "preg_id", "id");
    }

    public function elderlyImmu(){
        return $this->belongsTo(PhilImmelderly::class, "elder_id", "id");
    }

    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, "updated_by", "personnel_id")
            ->select("personnel_id", "pid");
    }


}