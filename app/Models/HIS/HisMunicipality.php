<?php

namespace App\Models\HIS;

use App\Models\PersonCatalog;
use Illuminate\Database\Eloquent\Model;

class HisMunicipality extends PersonCatalog
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_municity';

    protected $primaryKey = 'mun_nr';
    public $incrementing = false;
    public $timestamps = false;

}
