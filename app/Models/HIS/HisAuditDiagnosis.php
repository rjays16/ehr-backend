<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;
/**
 * @property String $id
 * @property String $date_changed
 * @property String $encoder
 * @property String $encounter_nr
 * @property String $old_final_diagnosis
 * @property String $old_other_diagnosis
 * @property String $tod
 */
class HisAuditDiagnosis extends Model
{
	protected $connection = 'his_mysql';
    protected $table = 'seg_audit_diagnosis';

    protected $primaryKey = 'encounter_nr';
  	protected $keyType = 'string';
  	public $incrementing = false;
 	public $timestamps = false;


}
