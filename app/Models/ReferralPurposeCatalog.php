<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralPurposeCatalog extends Model
{
    protected $table = 'smed_referral_purpose_catalog';
    public $timestamps = false;


    public function reason()
    {
        $model= self::query()
                ->select('id', 'purpose_desc')
                ->where('is_deleted', 0)
                ->get();

        $data = $temp = array();
        foreach ($model as $key => $val) {
            $temp['id'] = $val['id'];
            $temp['purpose_desc'] = $val['purpose_desc'];
            $data[] = $temp;
        }
        return $data;
    }
}
