<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
/**
 * @property String $spin
 * @property String $pid
 * @property String $date_registered
 * @property String $create_id
 * @property String $create_dt
 * @property String $modify_id
 * @property String $modify_dt
 */
class PatientCatalog extends Model
{
    protected $table = 'smed_patient_catalog';

    protected $primaryKey = 'spin';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'spin',
        'pid',
        'date_registered',
        'modify_id',
        'modify_dt',
        'create_id',
        'create_dt',
    ];

    protected $hidden = [
        'modify_id',
        'modify_dt',
        'is_doe',
        'doe_desc',
        'is_nbr',
        'multiple_birth',
    ];

    public function person(){
        return $this->belongsTo(PersonCatalog::class, 'pid');
    }

    public function p(){
        return $this->belongsTo(PersonCatalog::class, 'pid');
    }

    public function encounters(){
        return $this->hasMany(Encounter::class, 'spin')->orderByDesc("encounter_date");
    }


    public function getHospitalDate(Encounter $encounter)
    {
        $encDate = strtotime($encounter->encounter_date);

        if($encounter->is_discharged != 1) {
            $dischargeDate = strtotime(date('Y-m-d H:i:s'));
        }else {
            $dischargeDate = strtotime($encounter->discharge_dt);
        }

        $dateDiff = abs($encDate - $dischargeDate);
        $numberDays = $dateDiff/86400;

        return intval($numberDays)." Day/s";
    }


    public function getAge()
    {
        return strtr('{age}',[
            '{age}' => $this->person->getEstimatedAge().' old',
        ]);
    }
}
