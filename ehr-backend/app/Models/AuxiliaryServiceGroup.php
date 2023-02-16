<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuxiliaryServiceGroup extends Model
{
    protected $table = 'smed_auxiliary_service_group';

    protected $primaryKey = 'group_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
