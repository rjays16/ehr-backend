<?php


namespace App\API\V1\Models\pharmacy;


use Illuminate\Database\Eloquent\Model;

class MedsOrder extends Model
{
    protected $table = 'smed_meds_order';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

}