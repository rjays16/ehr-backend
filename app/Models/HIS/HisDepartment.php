<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

class HisDepartment extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'care_department';

    protected $primaryKey = 'nr';
    public $incrementing = false;
    public $timestamps = false;
}
