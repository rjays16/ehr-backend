<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

/**
 * File HisRadiologyServe.php
 *
 * @package App\Models\HIS
 * @property $refno
 * @property $encounter_nr
 * @property $request_date
 * @property $request_time
 * @property $pid
 * @property $is_cash
 * @property $status
 * @property $source_req
 * @property $is_printed
 * @property $comments
 * @property $history
 * @property $create_id
 * @property $type_charge
 */
class HisRadiologyServe extends Model
{
    protected $connection = 'his_mysql';
    protected $table = 'seg_radio_serv';

    protected $primaryKey = 'refno';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    public function details()
    {
        return $this->hasMany(HisRadiologyServeDetails::class, 'refno', 'refno');
    }

    public function getNewRefno($refno)
    {
        $temp_ref_nr = date('Y')."%";

        $result = self::query()
                ->select('refno')
                ->where('refno', 'LIKE', $temp_ref_nr)
                ->orderBy('refno', 'DESC')
                ->first();

        if($result){
            return $result['refno']+1;
        }else{
            return $refno;
        }
    }

}
