<?php

namespace App\Models\HIS;

use App\Models\Encounter;
use Illuminate\Database\Eloquent\Model;

class HisTypeEncounter extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'care_type_encounter';

    protected $primaryKey = 'type_nr';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    
}
