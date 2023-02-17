<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadioServiceGroup extends Model
{
    protected $table = 'smed_radio_service_group';

    protected $primaryKey = 'group_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
