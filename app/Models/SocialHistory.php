<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $spin
 * @property $encounter_no
 * @property $is_smoke
 * @property $years_smoking
 * @property $stick_per_day
 * @property $stick_per_year
 * @property $is_alcohol
 * @property $no_bottles
 * @property $is_drug
 * @property $remarks
 * @property $is_deleted
 * @property $created_dt
 * @property $create_id
 * @property $modify_dt
 * @property $modify_id
 * @property $created_at
 * @property $updated_at
 * @property $modified_at
 * @property $created_by
 * @property $modified_by
 */
class SocialHistory extends Model
{

    public $table = 'smed_social_history';
    const IS_DELETED = 0;
    const IS_NOT_DELETED = 0;

    public $fillable = [
        'id',
        'spin',
        'encounter_no',
        'is_smoke',
        'years_smoking',
        'stick_per_day',
        'stick_per_year',
        'is_alcohol',
        'no_bottles',
        'is_drug',
        'remarks',
        'is_deleted',
        'created_dt',
        'create_id',
        'modify_dt',
        'modify_id',
        'created_at',
        'updated_at',
        'modified_at',
        'created_by',
        'modified_by',
    ];

    //Relationships
    public function socialHistory($encounter){
        return self::query()
            ->where("encounter_no", $encounter)
            ->where("is_deleted", self::IS_NOT_DELETED)
            ->with("personnel.p")
            ->first();
    }

    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, "created_by", "personnel_id")
            ->select("personnel_id", "pid");
    }

}