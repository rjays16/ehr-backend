<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePackageCatalog extends Model
{
    protected $table = 'smed_service_package_catalog';
    protected $primaryKey = 'package_code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    public function group()
    {
        return $this->belongsTo(ServicePackageGroup::class,'group_id')
                    ->where('is_deleted',0)
                    ->orWhereNull('is_deleted');
    }

    public function recentPrice()
    {
        return $this->hasOne(PackagepriceCatalog::class,'package_code')
                    ->where('is_deleted','!=',1)
                    ->where('effectivity','<=', date("Y-m-d"))
                    ->orderByDesc('effectivity');
    }
}
