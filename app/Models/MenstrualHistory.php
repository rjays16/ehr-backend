<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $spin
 * @property $encounter_no
 * @property $age_first_menstrual
 * @property $last_period_menstrual
 * @property $no_days_menstrual_period
 * @property $interval_menstrual_period
 * @property $no_pads
 * @property $age_sex_intercourse
 * @property $birth_control_used
 * @property $is_menopause
 * @property $age_menopause
 * @property $remarks
 * @property $is_deleted
 * @property $created_at
 * @property $updated_at
 * @property $modified_at
 * @property $created_by
 * @property $modified_by
 * @property $is_applicable_menstrual
 */
class MenstrualHistory extends Model
{
    public $table = 'smed_menstrual_history';
    const IS_DELETED = 0;
    const IS_NOT_DELETED = 0;

    public $fillable = [
        'id',
        'spin',
        'encounter_no',
        'age_first_menstrual',
        'last_period_menstrual',
        'no_days_menstrual_period',
        'interval_menstrual_period',
        'no_pads',
        'age_sex_intercourse',
        'birth_control_used',
        'is_menopause',
        'age_menopause',
        'remarks',
        'is_deleted',
        'created_at',
        'updated_at',
        'modified_at',
        'created_by',
        'modified_by',
        'is_applicable_menstrual',
    ];

    public function menstrualHistory($encounter){
        return self::query()
            ->where("encounter_no", $encounter)
            ->where("is_deleted", self::IS_DELETED)
            ->first();
    }

    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, "modified_by", "personnel_id")
            ->select("personnel_id", "pid");
    }
}