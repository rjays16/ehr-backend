<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;
/**
 * @property String $icd_code
 * @property String $personell_nr
 * @property String $encounter_nr
 * @property String $create_id
 * @property String $create_time
 */
class HisDoctorsDiagnosis extends Model
{
	protected $connection = 'his_mysql';
  protected $table = 'seg_doctors_diagnosis';

 	protected $primaryKey = 'icd_code';
	protected $keyType = 'string';
	public $incrementing = false;
 	public $timestamps = false;

 	protected $fillable = [
        'icd_code',
        'personell_nr',
        'encounter_nr',
        'create_id',
        'create_time'
    ];

}
