<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HisLaboratoryServeDetails
 * @author Jan Chris Ogel <iamjc93@gmail.com>
 * @copyright 2020, Segworks Technologies Inc.
 * Date: 10/4/2020
 * Time: 5:11 PM
 *
 * @package App\Models\HIS
 * @property $refno
 * @property $service_code
 * @property $request_doctor
 * @property $price_cash
 * @property $price_cash_orig
 * @property $price_charge
 * @property $clinical_info
 * @property $status
 */
class HisLaboratoryServeDetails extends Model
{
    protected $connection = 'his_mysql';
    protected $table = 'seg_lab_servdetails';
    protected $primaryKey = ['refno', 'service_code'];
    protected $keyType = ['string', 'string'];
    public $incrementing = false;
    public $timestamps = false;

}
