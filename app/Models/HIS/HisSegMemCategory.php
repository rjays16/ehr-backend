<?php

namespace App\Models\HIS;

use App\Models\PersonnelCatalog;
use Illuminate\Database\Eloquent\Model;

class HisSegMemCategory extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_memcategory';

    protected $primaryKey = 'memcategory_id';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

}
