<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePackageGroup extends Model
{
    protected $table = 'smed_service_package_group';
    protected $primaryKey = 'group_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
