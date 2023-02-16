<?php


namespace App\Services\Person;


use App\Exceptions\EhrException\EhrException;
use App\Models\Encounter;
use App\Models\PatientCatalog;
use App\Models\PersonCatalog;
use Illuminate\Support\Facades\DB;

class PersonService
{

    private $_pid;


    function __construct($pid)
    {
        $this->_pid = PersonCatalog::query()->find($pid);
        if(!$this->_pid)
            throw new EhrException('Person does not exist.', 404);
    }

    public function getPersonEncounter(){
        $patientDatas = new Encounter();
        return $patientDatas->getPersonEncounters($this->_pid->pid);
    }


    
}