<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

class HisBillingCaserate extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_billing_caserate';

    protected $primaryKey = [
        'bill_nr','package_id','rate_type'
    ];
    protected $keyType = [
        'string','string','int'
    ];

    public $incrementing = false;
    public $timestamps = false;
}
