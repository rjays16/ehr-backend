<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $encounter_no
 * @property $doctor_id
 */
class FavoritePatient extends Model
{
    public $table = 'smed_favorite_patient';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public $fillable = [
        'id',
        'encounter_no',
        'doctor_id',
    ];

    public function encounter()
    {
        return $this->belongsTo(Encounter::class, 'encounter_no');
    }

    public function doctor()
    {
        return $this->belongsTo(PersonnelPermission::class, 'doctor_id','personnel_id');
    }
    
}
