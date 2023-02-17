<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HisLaboratoryServe
 * @author Jan Chris Ogel <iamjc93@gmail.com>
 * @copyright 2020, Segworks Technologies Inc.
 * Date: 10/3/2020
 * Time: 6:13 PM
 *
 * @package App\Models\HIS
 * @property $refno
 * @property $encounter_nr
 * @property $serv_dt
 * @property $serv_tm
 * @property $pid
 * @property $is_cash
 * @property $status
 * @property $ref_source
 * @property $source_req
 * @property $is_printed
 * @property $comments
 * @property $history
 * @property $create_id
 * @property $type_charge
 */
class HisLaboratoryServe extends Model
{
    protected $connection = 'his_mysql';
    protected $table = 'seg_lab_serv';

    protected $primaryKey = 'refno';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    public function details()
    {
        return $this->hasMany(HisLaboratoryServeDetails::class, 'refno', 'refno');
    }

}
