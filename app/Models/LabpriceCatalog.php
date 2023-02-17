<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabpriceCatalog extends Model
{
    protected $table = 'smed_labprice_catalog';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
