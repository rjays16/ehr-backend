<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property int $id
 * @property string $encounter_no
 * @property string $spin
 * @property string $disease_id
 * @property string $specific_disease_description
 * @property string $is_deleted
 * @property string $is_specific
 * @property string $created_at
 * @property string $created_by
 * @property string $deleted_by
 * @property string $deleted_at
 * */

class PastMedicalHistory extends Model
{

    public $table = 'smed_past_medical_history';
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

    //Relations
    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, "created_by", "personnel_id")
            ->select("personnel_id", "pid");
    }

    public function philDisease(){
        return $this->hasOne(PhilDisease::class, "disease_id", "disease_id");
    }

    //Queries
    public function pastMedicalHistory($encounter_no){
        return self::query()
            ->where("encounter_no", $encounter_no)
            ->with("philDisease", "personnel.p")
            ->get();
    }

    public function checkPastMedicalHistory($encounter){
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

}