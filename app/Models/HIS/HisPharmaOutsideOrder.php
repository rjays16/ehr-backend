<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

class HisPharmaOutsideOrder extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'care_pharma_outside_order';

    public $timestamps = false;
}
