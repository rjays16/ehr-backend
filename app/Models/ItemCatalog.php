<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCatalog extends Model
{
    protected $table = 'smed_item_catalog';

    protected $primaryKey = 'item_code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    protected $fillable = [
        'item_code',
        'drug_code',
        'item_name',
        'brand_name',
        'item_desc',
        'is_deleted',
        'inventory_typeid',
        'location',
        'modify_dt',
        'modify_id',
        'create_dt',
        'create_id',
        'is_pack',
        'is_manufactured',
        'is_fixed_price',
        'is_non_inventory',
        'is_forcecharge',
        'is_sterile',
        'is_batch_tracked',
        'is_serialized',
    ];

    public function item($item_code){
        return self::query()
            ->where("item_code", $item_code)
            ->first();
    }


    public function __toString()
    {
        return $this->item_name;
    }
}
