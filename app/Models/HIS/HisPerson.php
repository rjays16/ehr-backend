<?php

namespace App\Models\HIS;

use App\Models\PersonCatalog;
use Illuminate\Database\Eloquent\Model;

class HisPerson extends PersonCatalog
{
    protected $connection = 'his_mysql';

    protected $table = 'care_person';

    protected $primaryKey = 'pid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    public function getPatientAddress()
    {

        $barangay = $this->barangay ? $this->barangay->brgy_name : '';
        $municity = $this->municipality ? $this->municipality->mun_name : '';

        return $this->street_name . ', ' . $barangay . ', ' . $municity;
    }


    public function barangay(){
        return $this->belongsTo(HisBarangay::class, 'brgy_nr','brgy_nr');
    }

    public function municipality(){
        return $this->belongsTo(HisMunicipality::class, 'mun_nr','mun_nr');
    }

}
