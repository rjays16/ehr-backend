<?php

namespace App\Models\HIS;

use App\Models\PersonCatalog;
use Illuminate\Database\Eloquent\Model;

class HisBarangay extends PersonCatalog
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_barangays';

    protected $primaryKey = 'brgy_nr';
    public $incrementing = false;
    public $timestamps = false;

}
