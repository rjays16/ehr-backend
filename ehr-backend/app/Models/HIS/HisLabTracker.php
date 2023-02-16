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
 * @property $last_refno
 */
class HisLabTracker extends Model
{
    protected $connection = 'his_mysql';
    protected $table = 'seg_lab_tracker';

    protected $primaryKey = 'last_refno';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    public $fillable = ['last_refno'];
}
