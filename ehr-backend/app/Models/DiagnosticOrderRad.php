<?php

namespace App\Models;

use App\Models\Order\Diagnostic\RadiologyService;
use Illuminate\Database\Eloquent\Model;

/**
 * @property String $id
 * @property String $refno
 * @property String $encounter_no
 * @property String $order_groupid
 * @property String $doctor_id
 * @property String $service_id
 * @property String $is_stat
 * @property String $order_batchid
 * @property String $order_dt
 * @property String $is_cash
 * @property String $due_dt
 * @property String $remarks
 * @property String $is_portable
 * @property String $price_cash
 * @property String $price_cash_orig
 * @property String $price_charge
 * @property String $transaction_type
 * @property String $is_deleted
 * @property String $impression
 * @property String $is_served
 * @property String $date_served
 * @method static find($datum)
 *
 *
 * The followings are the available model relations:
 * @property LabsiteCollectionCatalog $siteCollection
 * @property AuxiliaryServiceOrder    $auxorder
 * @property Encounter                $encounterNo
 * @property OrderGroup               $orderGroup
 * @property RadioService             $service
 * @property BatchOrderNote           $orderBatch
 * @property PersonnelCatalog         $doctor
 */
class DiagnosticOrderRad extends Model
{
    protected $table = 'smed_diagnostic_order_rad';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    const STATUS_PENDING = 'pending';

    public function kardex()
    {
        return $this->hasMany(KardexRadiology::class, 'diagnosticorder_id');
    }


    public function orderBatch()
    {
        return $this->belongsTo(BatchOrderNote::class, 'order_batchid', 'id');
    }

    public function service()
    {
        return $this->belongsTo(RadioService::class, 'service_id');
    }

    public function auxiliaryServiceOrder()
    {
        return $this->belongsTo(AuxiliaryServiceOrder::class, 'auxorder_id');
    }

    /**
     * @inheritdoc
     * @return RadiologyService
     */
    public function getService():RadiologyService
    {
        return new RadiologyService($this->service ?: new RadioService());
    }
}
