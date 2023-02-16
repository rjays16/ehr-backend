<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

class HisPharmaItemsCf4 extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_pharma_items_cf4';

    public $incrementing = false;
    public $timestamps = false;
}
