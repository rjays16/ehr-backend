<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePackageOrder extends Model
{
    protected $table = 'smed_service_package_order';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
