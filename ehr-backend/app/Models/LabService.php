<?php

namespace App\Models;

use App\Models\HIS\HisGuiMgrDetails;
use Illuminate\Database\Eloquent\Model;

/**
 * @property String $service_id
 * @property String $service_name
 * @property String $group_id
 * @property String $price_cash
 * @property String $price_charge
 * @property String $opd_code
 * @property String $erd_code
 * @property String $ipd_code
 * @property String $is_profiletest
 * @property String $is_for_male
 * @property String $is_for_female
 * @property String $is_for_sendout
 * @property String $is_deleted
 * @property String $is_fixed_price
 * @property String $modify_id
 * @property String $modify_dt
 * @property String $create_id
 * @property String $create_dt
 * @property String $status
 * @property String $is_socialized
 * @property String $is_restricted
 */
class LabService extends Model
{
    protected $table = 'smed_lab_service';

    protected $primaryKey = 'service_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    public function group()
    {
        return $this->belongsTo(LabServiceGroup::class, 'group_id');
    }


    public function labPriceLatest()
    {
        return $this->hasOne(LabpriceCatalog::class, 'service_id','service_id')
                    ->orderByDesc('effectivity');
    }

    public function guiMgrDetails()
    {
        return $this->belongsTo(HisGuiMgrDetails::class, 'service_id','service_code');
    }
}
