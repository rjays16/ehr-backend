<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property int $id
 * @property string $encounter_no
 * @property string $spin
 * @property string $history
 * @property string $modified_by
 * @property string $created_by
 * */


class PresentIllness extends Model
{

    public $table = 'smed_history_present_illness';

    public $fillable = [
        'id',
        'encounter_no',
        'spin',
        'history',
        'created_by',
        'modified_by',
        'created_at',
        'updated_at'
    ];

    public function presentIllness($encounter){
        return self::query()
            ->where("encounter_no", $encounter)
            ->with("personnel.p")
            ->get();
    }

    public function personnel(){
        return $this->belongsTo(PersonnelCatalog::class, "created_by", "personnel_id")
            ->select("personnel_id", "pid");
    }

}