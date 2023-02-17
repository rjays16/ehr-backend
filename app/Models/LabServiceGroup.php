<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabServiceGroup extends Model
{
    protected $table = 'smed_lab_service_group';

    protected $primaryKey = 'group_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
