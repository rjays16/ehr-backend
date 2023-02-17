<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

/**
 * File HisRadiologyServeDetails.php
 *
 * @package App\Models\HIS
 * @property $batch_nr
 * @property $refno
 * @property $service_code
 * @property $request_doctor
 * @property $price_cash
 * @property $price_cash_orig
 * @property $price_charge
 * @property $clinical_info
 * @property $status
 */
class HisRadiologyServeDetails extends Model
{
    protected $connection = 'his_mysql';
    protected $table = 'care_test_request_radio';
    protected $primaryKey = ['refno', 'service_code'];
    protected $keyType = ['string', 'string'];
    public $incrementing = false;
    public $timestamps = false;

}
