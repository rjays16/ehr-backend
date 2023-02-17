<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property String $id
 * @property String $id
 * @property String $id
 * @property String $id
 * @property String $id
 * @property String $id
 * @property String $id
 * @property String $id
 */
class AuxiliaryServiceOrder extends Model
{
    protected $table = 'smed_auxiliary_service_order';

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


}
