<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property String $id
 * @property String $encounter_no
 * @property String $doctor_id
 * @property String $is_primary
 * @property String $modify_dt
 * @property String $modify_id
 * @property String $create_dt
 * @property String $create_id
 * @property String $is_deleted
 */
class EncounterDoctor extends Model
{
    protected $table = 'smed_encounter_doctor';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'encounter_no',
        'doctor_id',
        'is_primary',
        'modify_dt',
        'modify_id',
        'create_dt',
        'create_id',
        'is_deleted',
    ];

    public function encounter(){
        return $this->belongsTo(Encounter::class, 'encounter_no');
    }


    public function favorites($pesonnel_id = null){
        if(!$pesonnel_id)
            $q = $this->hasMany(FavoritePatient::class, 'doctor_id','doctor_id');
        else
            $q = FavoritePatient::query()->where('doctor_id', $pesonnel_id)->get();
        return $q;
    }

}
