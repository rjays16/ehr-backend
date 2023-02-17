<?php

namespace App\Models;

use App\Models\Order\Diagnostic\LaboratoryService;
use Illuminate\Database\Eloquent\Model;

/**
 * @property String $id
 * @property String $refno
 * @property String $encounter_no
 * @property int $order_groupid
 * @property String $doctor_id
 * @property String $service_id
 * @property int $is_stat
 * @property int $is_cash
 * @property String $transaction_type
 * @property int $charge
 * @property double $price_cash
 * @property double $price_cash_orig
 * @property double $price_charge
 * @property String $due_dt
 * @property String $remarks
 * @property String $order_batchid
 * @property String $order_dt
 * @property String $auxorder_id
 * @property String $site_collection
 * @property int $freq_id
 * @property int $series
 * @property double $quantity
 * @property String $impression
 * @property int $is_served
 * @property int $is_monitor
 * @property String $date_served
 * @property int $is_deleted
 * @method static find($datum)
 *
 * The followings are the available model relations:
 * @property LabsiteCollectionCatalog $siteCollection
 * @property AuxiliaryServiceOrder    $auxorder
 * @property Encounter                $encounterNo
 * @property OrderGroup               $orderGroup
 * @property LabService               $service
 * @property BatchOrderNote           $orderBatch
 * @property FrequencyCatalog         $freq
 * @property PersonnelCatalog         $doctor
 * @property KardexDiagnosticLab[]    $kardexDiagnosticLabs
 */
class DiagnosticOrderLab extends Model
{
    protected $table = 'smed_diagnostic_order_lab';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    const STATUS_PENDING = 'pending';


    public function auxiliaryServiceOrder()
    {
        return $this->belongsTo(AuxiliaryServiceOrder::class, 'auxorder_id');
    }


    public function orderBatch()
    {
        return $this->belongsTo(BatchOrderNote::class, 'order_batchid', 'id');
    }

    public function kardex()
    {
        return $this->hasMany(KardexLaboratory::class, 'diagnosticorder_id');
    }

    public function service()
    {
        return $this->belongsTo(LabService::class, 'service_id');
    }

    /**
     * @inheritdoc
     * @return LaboratoryService
     */
    public function getService():LaboratoryService
    {
        return new LaboratoryService($this->service ?: new LabService());
    }

    public function frequency(){
        return $this->belongsTo(PhilFrequency::class, 'freq_id','frequency_code');
    }

}
