<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;
/**
 * @property String $encounter_nr
 * @property String $clinical_impression
 * @property String $history
 */

class HisClinicalImpression extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_clinical_impression';

    protected $primaryKey = 'encounter_nr';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


}
