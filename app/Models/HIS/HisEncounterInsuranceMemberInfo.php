<?php

namespace App\Models\HIS;

use App\Models\PersonnelCatalog;
use Illuminate\Database\Eloquent\Model;

class HisEncounterInsuranceMemberInfo extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_encounter_insurance_memberinfo';

    protected $primaryKey = 'encounter_nr';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function member_category(){
        return $this->belongsTo(HisSegMemCategory::class, 'member_type','memcategory_code');
    }
}
