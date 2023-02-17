<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuxiliaryServiceCatalog extends Model
{
    protected $table = 'smed_auxiliary_service_catalog';

    protected $primaryKey = 'service_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function group()
    {
        return $this->belongsTo(AuxiliaryServiceGroup::class,'group_id');
    }


    public function auxPriceLatest()
    {
        return $this->belongsTo(AuxiliaryPriceCatalog::class,'service_id')
                    ->orderByDesc('effectivity');
    }
}
