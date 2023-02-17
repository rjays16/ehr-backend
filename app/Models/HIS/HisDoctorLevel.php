<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

class HisDoctorLevel extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_doctor_level';

    public $incrementing = false;
    public $timestamps = false;




    

}
