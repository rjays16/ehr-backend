<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadpriceCatalog extends Model
{
    protected $table = 'smed_radprice_catalog';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
