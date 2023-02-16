<?php


namespace App\API\V1\Models\pharmacy;


use Jenssegers\Mongodb\Eloquent\Model;

class PharmaHeader extends Model
{
    protected $table = 'smed_pharmaorder_h';

    protected $primaryKey = 'refno';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

}