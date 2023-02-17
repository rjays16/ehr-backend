<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KardexDischargeOrder extends Model
{
    protected $table = 'smed_kardex_discharge_order';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
