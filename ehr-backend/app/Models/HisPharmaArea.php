<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HisPharmaArea extends Model
{
    public $table = 'seg_pharma_areas';
    protected $connection = 'his_mysql';
    protected $primaryKey = 'area_code';
    public $timestamps = false;


    public function __toString()
    {
        return $this->area_name;
    }
}
