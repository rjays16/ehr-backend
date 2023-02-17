<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $encounter_no
 * @property $spin
 * @property $disease_id
 * @property $specific_disease_description
 * @property $is_deleted
 * @property $is_specific
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 * @property $created_by
 * @property $deleted_by
 */

class FamilyHistory extends Model
{
    public $table = 'smed_family_history';
    const IS_DELETED = 0;

    public $fillable = [
        'id',
        'encounter_no',
        'spin',
        'disease_id',
        'specific_disease_description',
        'is_deleted',
        'is_specific',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'deleted_by',
    ];

    public function familyHistory($encounter){
        return self::query()
            ->where("encounter_no", $encounter)
            ->with("philDisease", "personnel.p")
            ->get();
    }

    public function checkFamilyHistory($encounter){
        $query = self::query()->where([
            ["encounter_no", $encounter],
            ["is_deleted", 0],
            ["disease_id", 1]
        ])->get();

        foreach ($query as $key => $entry){
            if($entry->disease_id == 1){
                return true;
            }
        }
        return false;
    }

    //Relationships
    public function philDisease(){
        return $this->hasOne(PhilDisease::class, "disease_id", "disease_id");
    }

    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, "created_by", "personnel_id")
            ->select("personnel_id", "pid");
    }

}