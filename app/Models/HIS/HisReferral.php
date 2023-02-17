<?php


namespace App\Models\HIS;


use Illuminate\Database\Eloquent\Model;

class HisReferral extends Model
{
    protected $connection = 'his_mysql';

    protected $table = 'seg_referral';

    public $incrementing = false;
    public $timestamps = false;
}