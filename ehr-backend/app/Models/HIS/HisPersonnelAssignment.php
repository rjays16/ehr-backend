<?php

namespace App\Models\HIS;

use App\Models\PersonnelAssignment;
use Illuminate\Database\Eloquent\Model;

class HisPersonnelAssignment extends PersonnelAssignment
{
    protected $connection = 'his_mysql';

    protected $table = 'care_personell_assignment';

    protected $primaryKey = 'nr';
    public $incrementing = false;
    public $timestamps = false;
}
