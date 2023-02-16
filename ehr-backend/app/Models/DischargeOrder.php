<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DischargeOrder extends Model
{
    protected $table = 'smed_discharge_order';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    public function isCarriedOut()
    {
        return !!self::query()->whereHas('kardexDischargeOrder');
    }

    public function kardexDischargeOrder()
    {
        return $this->hasMany(KardexDischargeOrder::class, 'id','docorder_id');
    }

    /**
     * Returns the details of the discharge order as an array
     *
     * @return array
     */
    public function getDetails()
    {
        return json_decode($this->discharge_order) ?: [];
    }
}
