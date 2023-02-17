<?php


namespace App\API\V1\Models\radiology;


use App\API\V1\Models\Order;
use Illuminate\Database\Eloquent\Model;

class RadorderH extends Model
{


    protected $table = 'smed_radorder_h';

    protected $primaryKey = 'refno';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}