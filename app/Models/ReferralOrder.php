<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralOrder extends Model
{
    protected $table = 'smed_referral_order';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function isCarriedOut()
    {
        return !!self::query()->whereHas('kardexReferral');
    }

    public function kardexReferral()
    {
        return $this->hasMany(KardexReferral::class, 'id','docorder_id');
    }


    public function batch()
    {
        return $this->belongsTo(BatchOrderNote::class, 'order_batchid','id');
    }

    public function refto()
    {
        return $this->belongsTo(PersonnelCatalog::class, 'referto_doctorid','personnel_id');
    }

    public function department()
    {
        return $this->belongsTo(AreaCatalog::class, 'dept_id','area_id');
    }


    public function doctorArea()
    {
        return $this->belongsTo(AreaCatalog::class, 'area_id');
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
