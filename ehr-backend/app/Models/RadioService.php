<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadioService extends Model
{
    protected $table = 'smed_radio_service';

    protected $primaryKey = 'service_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    public function group()
    {
        return $this->belongsTo(RadioServiceGroup::class,'group_id');
    }


    public function latestRadpriceCatalog()
    {
        return $this->hasOne(RadpriceCatalog::class,'service_id')
                ->orderByDesc('effectivity');
    }
}
