<?php

namespace App\Models\HIS;

use App\Models\Encounter;
use Illuminate\Database\Eloquent\Model;

class HisEncounter extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'care_encounter';

    protected $primaryKey = 'encounter_nr';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;



    public function attendingPhysician(){
        return $this->belongsTo(HisPersonnel::class, 'current_att_dr_nr', 'nr');
    }

    public function typeEncounter(){
        return $this->belongsTo(HisTypeEncounter::class, 'encounter_type', 'type_nr');
    }

    public function ehrEncounter(){
        return $this->belongsTo(Encounter::class, 'encounter_nr', 'encounter_no');
    }

    public function attendingPhysicianDept(){
        return $this->belongsTo(HisDepartment::class, 'current_dept_nr', 'nr');
    }

    public function admittingPhysician(){
        return $this->belongsTo(HisPersonnel::class, 'consulting_dr_nr', 'nr');
    }

    public function admittingPhysicianDept(){
        return $this->belongsTo(HisDepartment::class, 'consulting_dept_nr', 'nr');
    }


    public function billing(){
        return $this->hasOne(HisBillingEncounter::class, 'encounter_nr', 'encounter_nr')
                    ->where(function($query){
                        $query->whereNull('is_deleted')
                              ->orWhere('is_deleted', 0);
                    });
    }
    


    public function getLocation()
    {
        if ($this->encounter_type == 1) {
            $loc = 'ER';
        } else if ($this->encounter_type == 2) {
            $loc = $this->consulting_dept_nr;
        } else {
            $loc = '';
        }
        return $loc;
    }
    

}
