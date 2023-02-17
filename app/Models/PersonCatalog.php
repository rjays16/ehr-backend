<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Model;
/**
 * @property String $pid
 * @property String $name_last
 * @property String $name_first
 * @property String $name_middle
 * @property String $suffix
 * @property String $gender
 * @property String $address_line1
 * @property String $contact_nos
 * @property String $email
 * @property String $birth_place
 * @property String $birth_date
 * @property String $create_id
 * @property String $create_dt
 * @property String $nationality_id
 * @property String $soundex_name_last
 * @property String $soundex_name_first
 * @property String $soundex_name_middle
 * @property String $is_deleted
 * @property String $modify_id
 * @property String $modify_dt
 */
class PersonCatalog extends Model
{

    public $personSearch = '';

    protected $table = 'smed_person_catalog';

    protected $primaryKey = 'pid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    protected $fillable = [
        'pid',
        'name_last',
        'name_first',
        'name_middle',
        'suffix',
        'gender',
        'address_line1',
        'contact_nos',
        'email',
        'birth_place',
        'birth_date',
        'create_id',
        'create_dt',
        'nationality_id',
        'soundex_name_last',
        'soundex_name_first',
        'soundex_name_middle',
        'is_deleted',
        'modify_id',
        'modify_dt',
    ];


    protected $hidden = [
        "address_line2",
        "brgy_code",
        "lgu_code",
        "prov_code",
        "foreign_address",
        "classification_id",
        "civil_status_id",
        "id_no",
        "id_code",
        "nationality_id",
        "is_deleted",
        "occupation_id",
        "occupation_specific",
        "ethnic_id",
        "bloodtype_id",
        "brgy_name",
        "picture",
    ];


    public function person(){
        return $this->belongsTo(PatientCatalog::class, 'pid');
    }

    public function activeEncounters(){
//        return $this->
    }


    public function getFormattedGender()
    {
        if (!empty($this->gender)) {
            switch ($this->gender) {
                case 'M':
                    return 'Male';
                case 'F':
                    return 'Female';
                case 'f':
                    return 'Female';
                case 'm':
                    return 'Male';
                case 'A':
                    return 'Ambiguous';
            }
        }

        return 'Unknown';
    }

    /**
     * Returns the full name of the [erson in LASTNAME, FIRSTNAME MIDDLENAME
     * format.
     *
     * @return string
     */
    public function getFullname()
    {
        $name = trim($this->name_last).', '.trim($this->name_first).' '.trim($this->name_middle);
        if ($this->suffix) {
            return $name.' '.trim($this->suffix);
        } else {
            return $name;
        }
    }

    /**
     * Returns the full name of the [erson in LASTNAME, FIRSTNAME MIDDLENAME
     * format.
     *
     * @return string
     */
    public function fullname()
    {
        return $this->getFullname();
    }


    public function drFullname():string
    {
        return "Dr. {$this->getFullname()}";
    }


    /**
     * Returns the human-readable age of the person which could be in
     * years, months or days, whichever is applicable.
     *
     * @param string $referenceDate
     *
     * @return string
     */
    public function getEstimatedAge($referenceDate = null, $birth_date = null)
    {
        $this->birth_date = is_null($birth_date) ? $this->birth_date : $birth_date;
        
        $oDateNow = new DateTime($referenceDate);
        $oDateBirth = new DateTime($this->birth_date);
        $oDateIntervall = $oDateNow->diff($oDateBirth);
        if ($oDateIntervall->y < 1) {
            if ($oDateIntervall->m < 1) {
                return $oDateIntervall->d." day/s";
            } else {
                return $oDateIntervall->m." mo/s";
            }
        } else
            return $oDateIntervall->y." yr/s";
    }


    /**
     * Returns the age of the person in years
     *
     * @return int
     */
    public function getAge()
    {
        $oDateNow = new DateTime();
        $oDateBirth = new DateTime($this->birth_date);
        $oDateIntervall = $oDateNow->diff($oDateBirth);

        return $oDateIntervall->y;
    }

    public function getFullAge()
    {

        $oDateNow = new DateTime();
        $oDateBirth = new DateTime($this->birth_date);
        $oDateIntervall = $oDateNow->diff($oDateBirth);

        if ($oDateIntervall->y >= 1) {
            return $oDateIntervall->y." year(s) ".$oDateIntervall->m." month(s)";
        }else{
            return $oDateIntervall->m." month(s) ".$oDateIntervall->d." day(s)";
        }

    }
}
