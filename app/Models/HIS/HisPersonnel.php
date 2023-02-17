<?php

namespace App\Models\HIS;

use App\Models\PersonnelCatalog;
use Illuminate\Database\Eloquent\Model;

class HisPersonnel extends PersonnelCatalog
{
    protected $connection = 'his_mysql';

    protected $table = 'care_personell';

    protected $primaryKey = 'nr';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function admittingPhysician(){
        return $this->belongsTo(HisPerson::class, 'pid');
    }

    public function p(){
        return $this->belongsTo(HisPerson::class, 'pid');
    }


    public function assignments(){
        return $this->hasMany(HisPersonnelAssignment::class, 'nr', 'personnel_nr')
                        ->where('status','<>','deleted');
    }

    public function doctorLevel(){
        return $this->belongsTo(HisDoctorLevel::class, 'doctor_level', 'id');
    }

}
