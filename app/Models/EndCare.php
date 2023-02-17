<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
/**
 * @property $id
 * @property $encounter_no
 * @property $doc_advice
 * @property $date
 * @property $time
 * @property $treatment
 * @property $reason
 * @property $modify_id
 * @property $modify_dt
 * @property $create_id
 * @property $create_dt
 */

class EndCare extends Model
{

    public $table = 'smed_end_care';
    public $timestamps = false;

    public $fillable = [
        'id',
        'encounter_no',
        'doc_advice',
        'date',
        'time',
        'treatment',
        'reason',
        'modify_id',
        'modify_dt',
        'create_id',
        'create_dt',
    ];

    public $hidden = [
        'updated_at'
    ];

    public function encounter(){
        return $this->belongsTo(Encounter::class, 'encounter_no');
    }

    public function getEndCare($encounter){
        return self::query()
            ->where("encounter_no", $encounter)
            ->with("encounter")
            ->first();
    }
}