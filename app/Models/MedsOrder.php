<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
* @property String $id
* @property String $item_id
* @property String $custom_item
* @property int $is_custom
* @property String $order_batchid
* @property int $order_groupid
* @property int $frequency_id
* @property String $sig
* @property String $method
* @property String $route
* @property String $timing
* @property datetime $order_dt
* @property String $duration
* @property decimal $quantity
* @property String $remarks
* @property int $is_stat
* @property int $is_cash
* @property String $drug_code
* @property String $encounter_no
* @property int $is_ehr
* @property date $order_date
* @property time $order_time
* @property int $order_check
 * @method static find($datum)
 */
class MedsOrder extends Model
{
    protected $table = 'smed_meds_order';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'item_id',
        'custom_item',
        'is_custom',
        'order_batchid',
        'order_groupid',
        'frequency_id',
        'sig',
        'method',
        'route',
        'timing',
        'order_dt',
        'duration',
        'quantity',
        'remarks',
        'is_stat',
        'is_cash',
        'drug_code',
        'encounter_no',
        'is_ehr',
        'order_date',
        'order_time',
        'order_check',
    ];

    public function frequencyCatalog()
    {
        return $this->belongsTo(FrequencyCatalog::class,'frequency_id','id');
    }

    public function item()
    {
        return $this->belongsTo(ItemCatalog::class,'item_id','item_code');
    }

    public function orderBatch()
    {
        return $this->belongsTo(BatchOrderNote::class,'order_batchid','id');
    }

}
