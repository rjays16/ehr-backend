<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuxiliaryPriceCatalog extends Model
{
    protected $table = 'smed_auxiliary_price_catalog';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
