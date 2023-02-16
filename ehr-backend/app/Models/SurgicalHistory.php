<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property int $spin
 * @property int $encounter_no
 * @property int $date_of_operation
 * @property int $description
 * @property int $remarks
 * @property int $is_deleted
 * @property int $created_by
 * @property int $deleted_by
 * @property int $deleted_at
 */

class SurgicalHistory extends Model
{
    public $table = 'smed_surgical_history';
    const IS_DELETED = 0;

    public $fillable = [
        'id',
        'spin',
        'encounter_no',
        'date_of_operation',
        'description',
        'remarks',
        'is_deleted',
        "created_at",
        "updated_at",
        "deleted_at",
        "created_by",
        "deleted_by"
    ];

    //Queries
    public function surgicalHistory($encounter){
        return self::query()
            ->where("encounter_no", $encounter)
            ->with("personnel.p")
            ->get();
    }

    //Relationships
    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, "created_by", "personnel_id")
            ->select("personnel_id", "pid");
    }


}