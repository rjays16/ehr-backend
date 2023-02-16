<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

class HisPharmaProductsMain extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'care_pharma_products_main';

    protected $primaryKey = 'bestellnum';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
