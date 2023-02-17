<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

class HisRadioID extends Model
{
	protected $connection = 'his_mysql';
    protected $table = 'seg_radio_id';

  	protected $keyType = 'string';
  	public $incrementing = false;
 	public $timestamps = false;

}
