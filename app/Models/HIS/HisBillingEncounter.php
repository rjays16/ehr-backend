<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Model;

class HisBillingEncounter extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_billing_encounter';

    protected $primaryKey = 'bill_nr';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function caserate()
    {
        return $this->hasMany(HisBillingCaserate::class, 'bill_nr','bill_nr');
    }

    /**
     * @param $encounter_no
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getFinalBill($encounter_no){
        return self::query()->select('is_final',  'is_deleted')->where('encounter_nr', $encounter_no)
            ->where(function ($query){
                $query->where('is_deleted', '=', null)
                    ->orWhere('is_deleted', '=', 0);
            })->first();
    }
}
