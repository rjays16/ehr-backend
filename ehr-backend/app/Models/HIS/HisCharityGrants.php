<?php

namespace App\Models\HIS;

use App\Models\PersonnelCatalog;
use Illuminate\Database\Eloquent\Model;

class HisCharityGrants extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_charity_grants';

    protected $primaryKey = 'encounter_nr';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function his_discount(){
        return $this->belongsTo(HisDiscount::class, 'discountid','discountid');
    }
}
