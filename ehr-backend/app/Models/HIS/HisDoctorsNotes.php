<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\HisEncounter; 
/**
 * @property String $encounter_nr
 * @property String $personell_nr
 * @property String $chief_complaint
 * @property String $physical_examination
 * @property String $clinical_summary
 * @property String $history
 * @property String $create_id
 * @property String $create_time
 */
class HisDoctorsNotes extends Model
{
	protected $connection = 'his_mysql';
  protected $table = 'seg_doctors_notes';

 	protected $primaryKey = 'personell_nr';
	protected $keyType = 'string';
	public $incrementing = false;
 	public $timestamps = false;

 	public function encounters() {
		return $this->hasOne(HisEncounter::class, 'encounter_nr', 'encounter_nr');
	}

}
