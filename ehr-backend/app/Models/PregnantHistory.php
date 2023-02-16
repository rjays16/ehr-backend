<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $spin
 * @property $encounter_no
 * @property $date_gravidity
 * @property $date_parity
 * @property $type_delivery
 * @property $no_full_term_preg
 * @property $no_premature
 * @property $no_abortion
 * @property $no_living_children
 * @property $induced_hyper
 * @property $family_planning
 * @property $remarks
 * @property $is_deleted
 * @property $created_at
 * @property $updated_at
 * @property $modified_at
 * @property $created_by
 * @property $modified_by
 * @property $is_applicable_pregnant
 */
class PregnantHistory extends Model
{
    public $table = 'smed_pregnant_history';
    const IS_DELETED = 0;
    const IS_NOT_DELETED = 0;

    public $fillable = [
        'id',
        'spin',
        'encounter_no',
        'date_gravidity',
        'date_parity',
        'type_delivery',
        'no_full_term_preg',
        'no_premature',
        'no_abortion',
        'no_living_children',
        'induced_hyper',
        'family_planning',
        'remarks',
        'is_deleted',
        'created_at',
        'updated_at',
        'modified_at',
        'created_by',
        'modified_by',
        'is_applicable_pregnant',
    ];

    public function pregnantHistory($encounter){
        return self::query()
            ->where("encounter_no", $encounter)
            ->where("is_deleted", self::IS_NOT_DELETED)
            ->with("personnel.p")
            ->first();
    }

    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, "modified_by", "personnel_id")
            ->select("personnel_id", "pid");
    }

}