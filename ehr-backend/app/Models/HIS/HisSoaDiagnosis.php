<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;
/**
 * @property String $encounter_nr
 * @property String $final_diagnosis
 * @property String $other_diagnosis
 * @property String $create_date
 * @property String $create_id
 * @property String $modify_date
 * @property String $modify_id
 * @property String $history
 */

class HisSoaDiagnosis extends Model
{
	protected $connection = 'his_mysql';
    protected $table = 'seg_soa_diagnosis';

    protected $primaryKey = 'encounter_nr';
  	protected $keyType = 'string';
  	public $incrementing = false;
 	public $timestamps = false;


}
