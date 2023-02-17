<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property $id
 * @property $encounter_no
 * @property $spin
 * @property $summary
 * @property $created_by
 * @property $updated_by
 * @property $created_at
 * @property $updated_at
 */
class MedicalHistorySummary extends Model
{

    public $table = 'smed_medical_history_summary';

    public $fillable = [
        'id',
        'encounter_no',
        'spin',
        'summary',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];

    public function medicalHistorySummary($encounter){
        return self::query()->where("encounter_no", $encounter)->first();
    }


}