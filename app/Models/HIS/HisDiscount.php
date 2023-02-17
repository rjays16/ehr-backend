<?php

namespace App\Models\HIS;

use App\Models\PersonnelCatalog;
use Illuminate\Database\Eloquent\Model;

class HisDiscount extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_discount';

    protected $primaryKey = 'discountid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

}
